from troposphere import (
    Ref, Join, Sub, Base64, awslambda, cloudformation, Parameter, Template, ssm, ImportValue, iam, If
)
from awacs.aws import Policy, Allow, Statement, Principal, Action

from cfn_encrypt import Encrypt, EncryptionContext, SecureParameter


def lambda_from_file(python_file):
    """
    Reads a python file and returns a awslambda.Code object
    :param python_file:
    :return:
    """
    lambda_function = []
    with open(python_file, 'r') as f:
        lambda_function.extend(f.read().splitlines())

    return awslambda.Code(ZipFile=(Join('\n', lambda_function)))


def get_metadata(instance, volume):
    return cloudformation.Init(
        cloudformation.InitConfigSets(
            install=["install_cfn"],
            update=["resize"]

        ),
        install_cfn=cloudformation.InitConfig(
            files={
                "/etc/cfn/cfn-hup.conf": {
                    "content": Join("", [
                        "[main]\n",
                        "stack=", Ref("AWS::StackId"), "\n",
                        "region=", Ref("AWS::Region"), "\n",
                        "interval=1\n",
                        "verbose=true\n"

                    ]),
                    "mode": "000400",
                    "owner": "root",
                    "group": "root"
                },
                "/etc/cfn/hooks.d/cfn-auto-reloader.conf": {
                    "content": Join("", [
                        "[cfn-auto-reloader-hook]\n",
                        "triggers=post.update\n",
                        "path=Resources.{}\n".format(volume),
                        "action=/opt/aws/bin/cfn-init ",
                        " --stack ", Ref("AWS::StackName"),
                        " --resource {} ".format(instance),
                        " --configsets update ",
                        " --region ", Ref("AWS::Region")
                    ]),
                    "mode": "000400",
                    "owner": "root",
                    "group": "root"
                },
            },
            services={
                "sysvinit": {
                    "cfn-hup": {
                        "enabled": "true",
                        "ensureRunning": "true",
                        "files": ["/etc/cfn/cfn-hup.conf", "/etc/cfn/hooks.d/cfn-auto-reloader.conf"]
                    }

                }
            }
        ),

        resize=cloudformation.InitConfig(
            commands={
                "resize": {
                    "command": "/sbin/resize2fs /dev/xvdh",
                    "env": {"HOME": "/root"}

                }

            }
        ),

    )


def userdata_file_path(name):
    return name + '.sh'


def userdata_from_file(userdata_file, parameters=None, references=None,
                       constants=None):
    """
    creates userdata for troposphere and cloudinit
    inserts `parameters` at the top of the script

    :type parameters: list
    :param parameters: list of troposphere.Parameters()

    :type references
    :param parameters: list of troposphere.Ref()

    :type constants
    :param constants: list of tuple(key, value)

    :rtype: troposphere.Base64()
    :return: troposphere ready to consume userdata
    """
    userdata = ['#!/bin/bash\n']
    if parameters is None:
        parameters = []

    if references is None:
        references = []

    if constants is None:
        constants = []

    for param in parameters:
        variable_name = param.title
        userdata = userdata + [variable_name] + ['='] + [Ref(param)] + ['\n']

    for ref in references:
        # Create variable name from Ref function
        # Example: {"Ref": "AWS::Region"} -> AWSRegion
        variable_name = ref.data['Ref'].replace('::', '')
        userdata = userdata + [variable_name] + ['='] + [ref.data] + ['\n']

    for constant in constants:
        userdata = userdata + [constant[0]] + ['='] + [constant[1]] + ['\n']

    # append the actial file
    with open(userdata_file, 'r') as f:
        userdata.extend(f.readlines())

    return Base64(Join('', userdata))


class NCTemplate(Template):
    def __init__(self, description="Troposphere template with ParameterStore helpers"):
        super(NCTemplate, self).__init__()
        self.add_description(description)
        """
        Provides attach_ssm_policy() and add_ssm_parameters() for ease of use
        """

    def attach_ssm_policy(self, myrole):
        return super(NCTemplate, self).add_resource(iam.PolicyType(
            'policyssm',
            PolicyName='ssmpolicy',
            PolicyDocument=Policy(
                Version='2012-10-17',
                Statement=[
                    Statement(
                        Effect=Allow,
                        Action=[
                            Action("ssm", "DescribeParameters")
                        ],
                        Resource=["*"]
                    ),
                    Statement(
                        Effect=Allow,
                        Action=[
                            Action("ssm", "GetParameters")
                        ],
                        Resource=[
                            Join(
                                "",
                                [
                                    "arn:aws:ssm", ":",
                                    Ref("AWS::Region"), ":",
                                    Ref("AWS::AccountId"), ":",
                                    "parameter/",
                                    Ref("AWS::StackName"),
                                    "/*"
                                ]
                            ),
                            Join(
                                "",
                                [
                                    "arn:aws:ssm", ":",
                                    Ref("AWS::Region"), ":",
                                    Ref("AWS::AccountId"), ":",
                                    "parameter/",
                                    "globals",
                                    "/*"
                                ]
                            ),
                        ]
                    ),
                ]
            ),
            Roles=[Ref(myrole)]
        ))

    def attach_ssm_key_policy(self, myrole):
        return super(NCTemplate, self).add_resource(iam.PolicyType(
            'policyssmkey',
            PolicyName='ssmkeypolicy',
            PolicyDocument=Policy(
                Version='2012-10-17',
                Statement=[
                    Statement(
                        Effect=Allow,
                        Action=[
                            Action("kms", "Decrypt")
                        ],
                        Resource=[
                            ImportValue(Sub("${EncryptLambdaStack}-KmsKeyArn"))
                        ]

                    )
                ]
            ),
            Roles=[Ref(myrole)]
        ))

    def add_ssm_parameters(self, Params=None, EncParams=None, Global=False):
        varparams = {}
        path = "globals" if Global else Ref("AWS::StackName")
        if Params is None and EncParams is None:
            raise ValueError('add_ssm_parameters requires {Params:Value} or [EncParams]')
        if Params is None:
            Params = {}
        if EncParams is None:
            EncParams = []
        for (paramname, paramvalue) in Params.items():
            varparams[paramname] = super(NCTemplate, self).add_parameter(Parameter(
                paramname.replace('_', ''),
                Type="String",
                Description="Environment variable " + paramname,
                NoEcho=False,
                Default=paramvalue
            ))
            super(NCTemplate, self).add_resource(ssm.Parameter(
                "Param" + paramname.replace('_', ''),
                Name=Join("/", ["", path, paramname]),
                Type="String",
                Description="Environment variable " + paramname,
                Value=Ref(varparams[paramname])
            ))
        for paramname in EncParams:
            varparams[paramname] = super(NCTemplate, self).add_parameter(Parameter(
                paramname.replace('_', ''),
                Type="String",
                Description="Encrypted environment variable " + paramname,
                NoEcho=True,
            ))
            super(NCTemplate, self).add_resource(SecureParameter(
                "Param" + paramname.replace('_', ''),
                Name=Join("/", ["", Ref("AWS::StackName"), paramname]),
                ServiceToken=ImportValue(Sub("${EncryptLambdaStack}-SsmParameterLambdaArn")),
                Description="Encrypted environment variable " + paramname,
                Value=Ref(varparams[paramname]),
                KeyId=ImportValue(Sub("${EncryptLambdaStack}-KmsKeyArn"))
            ))
        return varparams


class UserData(object):
    def __init__(self):
        self.shell_scripts = []
        self.upstart_jobs = []

    def add_file(self, userdata_file, type="shell", parameters=None, references=None,
                 constants=None):
        userdata = []
        if parameters is None:
            parameters = []

        if references is None:
            references = []

        if constants is None:
            constants = []

        for param in parameters:
            variable_name = param.title
            userdata = userdata + [variable_name] + ['='] + [Ref(param)] + ['\n']

        for ref in references:
            # Create variable name from Ref function
            # Example: {"Ref": "AWS::Region"} -> AWSRegion
            variable_name = ref.data['Ref'].replace('::', '')
            userdata = userdata + [variable_name] + ['='] + [ref.data] + ['\n']

        for constant in constants:
            userdata = userdata + [constant[0]] + ['='] + [constant[1]] + ['\n']

        with open(userdata_file, 'r') as f:
            userdata.extend(f.readlines())
        if type == "shell":
            self.shell_scripts.append(userdata)
        elif type == "upstart":
            self.upstart_jobs.append(userdata)
        else:
            raise NameError("Type: must be either shell or upstart")

    def get_user_data(self):
        ret = []
        boundary = "==BOUNDARY=="
        ret.append('Content-Type: multipart/mixed; boundary="{}"\n'.format(boundary))
        boundary += '\n'
        ret.append('MIME-Version: 1.0\n')

        for u in self.upstart_jobs:
            ret.append('\n--{0}'.format(boundary))
            ret.append('MIME-Version: 1.0\n')
            ret.append('Content-Type: text/upstart-job; charset="us-ascii"\n')
            ret.extend(u)

        for sh in self.shell_scripts:
            ret.append('\n--{0}'.format(boundary))
            ret.append('MIME-Version: 1.0\n')
            ret.append('Content-Type: text/x-shellscript; charset="us-ascii"\n')
            ret.extend(sh)

        return Base64(Join('', ret))
