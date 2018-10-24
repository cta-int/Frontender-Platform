from automation import NCTemplate
from awacs.aws import Action, Allow, Policy, Principal, Statement
from troposphere import (
    applicationautoscaling, cloudwatch, ec2, ecs, elasticloadbalancingv2, iam, logs, ssm,
    Equals, GetAZs, GetAtt, If, ImportValue, Join, Not, Parameter, Ref, Select, Sub
)
from cfn_encrypt import Encrypt, EncryptionContext, SecureParameter
import cfn_datadog
from awacs.s3 import ARN as S3_ARN
t = NCTemplate()

t.add_description("scr-platform service")

"""
Parameters
"""

container_name = t.add_parameter(Parameter(
    "ContainerName",
    Type="String",
    Description="Container name",
    Default="scr-platform"
))

container_port = t.add_parameter(Parameter(
    "ContainerPort",
    Type="Number",
    Description="Container port",
    Default="8080"
))

scr_hostname = t.add_parameter(Parameter(
    "ScrHostname",
    Description="Url of repo",
    Type="String"
))

ecr = t.add_parameter(Parameter(
    "Ecr",
    Type="String",
    Description="ECR",
    Default="597442228408.dkr.ecr.eu-central-1.amazonaws.com"
))

ecs_stack = t.add_parameter(Parameter(
    "EcsStack",
    Type="String",
    Description="ECS Stackname",
    Default="ecs-stateful"
))

encrypt_lambda_stack = t.add_parameter(Parameter(
    "EncryptLambdaStack",
    Type="String",
    Description="Encrypt Lambda Stackname",
    Default=""
))

encrypt_lambda_stack_condition = "EncryptLambdaStackCondition"
t.add_condition(encrypt_lambda_stack_condition, Not(Equals("", Ref(encrypt_lambda_stack))))

family = t.add_parameter(Parameter(
    "Family",
    Type="String",
    Description="Task family",
    Default="scr-platform"
))

image_name = t.add_parameter(Parameter(
    "ImageName",
    Type="String",
    Description="Docker image name",
    Default="scr-platform"
))

image_tag = t.add_parameter(Parameter(
    "ImageTag",
    Type="String",
    Description="Docker image tag",
    Default="latest"
))

listener_priority = t.add_parameter(Parameter(
    "ListenerPriority",
    Description="Listener Rule Priority, must be unique across listeners",
    Type="Number",
    Default="10"
))

network_stack = t.add_parameter(Parameter(
    "NetworkStack",
    Type="String",
    Description="ECS network stack name",
    Default="network"
))

service_path = t.add_parameter(Parameter(
    "ServicePath",
    Type="String",
    Description="Path portion of the service URL",
    Default="/*"
))

service_host = t.add_parameter(Parameter(
    "ServiceHost",
    Type="String",
    Description="Hostname for the listener (optional)",
    Default=""
))

service_host_condition = "ServiceHostCondition"
t.add_condition(service_host_condition, Not(Equals("", Ref(service_host))))

autoscaling_max = t.add_parameter(Parameter(
    "AutoscalingMax",
    Type="Number",
    Description="Maximum number of tasks to autoscale",
    Default=3
))

autoscaling_min = t.add_parameter(Parameter(
    "AutoscalingMin",
    Type="Number",
    Description="Minimum number of tasks to autoscale",
    Default=3
))

health_check_path = t.add_parameter(Parameter(
    "HealthCheckPath",
    Type="String",
    Description="Healthcheck path",
    Default="/"
))

real_entrypoint = t.add_parameter(Parameter(
    "RealEntrypoint",
    Type="String",
    Description="Entrypoint for container (CMD from Dockerfile)",
    Default="/aws-start.sh"
))
datadog_stack = t.add_parameter(Parameter(
    "DatadogStack",
    Type="String",
    Default="cfn-datadog"
))
stack_env = t.add_parameter(Parameter(
    "StackEnv",
    Type="String",
    AllowedValues=["PROD", "UAT", "OTHER"],
    Description="When PROD is selected dsaas will be installed on the instances. Use UAT for UAT stacks and OTHER for everything else",
    Default="OTHER"
))
is_prod = "IsProd"
t.add_condition(is_prod, Equals("PROD", Ref(stack_env)))
service_host_condition = "ServiceHostCondition"
t.add_condition(service_host_condition, Not(Equals("", Ref(service_host))))

t.add_resource(cfn_datadog.MetricAlert(
    "DDAlarmServiceCount",
    Condition=is_prod,
    ServiceToken=ImportValue(Sub("${DatadogStack}-LambdaArn")),
    name="elastic search service count ( CFN )",
    query=Sub("avg(last_1h):avg:aws.ecs.service.running{service:${Service.Name}} < 0"),
    message="Less than 1  ES services running @pagerduty",
    options=cfn_datadog.MetricAlertOptions(
        timeout_h=0,
        notify_no_data=True,
        no_data_timeframe=30,
        notify_audit=False,
        require_full_window=False,
        new_host_delay=300,
        include_tags=False,
        escalation_message="",
        locked=False,
        renotify_interval="0",
        evaluation_delay="900",
        thresholds=cfn_datadog.Thresholds(
            critical=0,
            warning=1
        )
    )
))
log_group = t.add_resource(logs.LogGroup(
    "LogGroup",
    LogGroupName=Ref("AWS::StackName"),
    RetentionInDays=60
))

"""
Environment variables for tasks providing this service
 - Will be stored in AWS Parameter Store
 - Simple dict
   - Value used as Default for template Parameters
 - Name in Parameter Store will be built like "/StackName/MYSQL_PASSWORD"
 - DO NOT add secret variables to this file, insert them when deploying the stack.
   - Template generated from this will be viewable in Cloudformation console and saved to S3. 
   - Encrypted parameters passed to the stack will be stored directly to ParameterStore with encryption.
 - Global Parameters are truly global - you can not define same global parameter from multiple stacks
"""

vars = {}
# Creates a dependency on EncryptLambdaStack
encvars = []

# If pipeline will put encrypted parameters in /StackName/ namespace
pipeline_encvars = True

# ssm_params = t.add_ssm_parameters(Params=vars, EncParams=encvars)
# global_params = t.add_ssm_parameters(Params={"A_GLOBAL_ENV_VAR": "This is global"}, Global=True)

"""
Roles
 - TaskRole
 - ServiceRole
 - AutoscaleRole
"""

task_role = t.add_resource(iam.Role(
    "TaskRole",
    AssumeRolePolicyDocument=Policy(
        Version="2012-10-17",
        Statement=[
            Statement(
                Effect=Allow,
                Principal=Principal("Service", "ecs-tasks.amazonaws.com"),
                Action=[Action("sts", "AssumeRole")]
            )
        ]
    ),
    Path="/",
    Policies=[
        iam.Policy(
            PolicyName=Join("-", [Ref("AWS::StackName"), "TaskPolicy"]),
            PolicyDocument=Policy(
                Version="2012-10-17",
                Statement=[
                    Statement(
                        Effect=Allow,
                        Action=[
                            Action("logs", "CreateLogStream"),
                            Action("logs", "PutLogEvents"),
                            Action("logs", "CreateLogGroup"),
                        ],
                        Resource=[
                            Join(
                                ":",
                                [
                                    "arn:aws:logs",
                                    Ref("AWS::Region"),
                                    Ref("AWS::AccountId"),
                                    "log-group", Ref(log_group), "*"
                                ]
                            )
                        ]
                    ),
                    
                    Statement(
                        Effect=Allow,
                        Action=[
                            Action("s3","*")
                        ],
                        Resource=[
                            S3_ARN("cta-pages"),
                            S3_ARN("cta-pages/*"),

                        ]
                    )
                ]
            )
        )
    ]
))

# Attach a policy with attach_ssm_policy that allows listing and reading of parameters from ParameterStore
# If we have any encrypted variables, attach a policy to allow using the KMS Key exported by EncryptLambdaStack
# PR's welcome

ssm_policy = t.attach_ssm_policy(task_role)
if (len(encvars) > 0 or pipeline_encvars):
    ssm_key_policy = t.attach_ssm_key_policy(task_role)

service_role = t.add_resource(iam.Role(
    "ServiceRole",
    AssumeRolePolicyDocument=Policy(
        Version="2012-10-17",
        Statement=[
            Statement(
                Effect=Allow,
                Principal=Principal("Service", "ecs.amazonaws.com"),
                Action=[Action("sts", "AssumeRole")]
            )
        ]
    ),
    Path="/",
    Policies=[
        iam.Policy(
            PolicyName=Join("-", [Ref("AWS::StackName"), "ServicePolicy"]),
            PolicyDocument=Policy(
                Version="2012-10-17",
                Statement=[
                    Statement(
                        Effect=Allow,
                        Action=[
                            Action("ec2", "AuthorizeSecurityGroupIngress"),
                            Action("ec2", "Describe*"),
                            Action("elasticloadbalancing", "DeregisterInstancesFromLoadBalancer"),
                            Action("elasticloadbalancing", "DeregisterTargets"),
                            Action("elasticloadbalancing", "Describe*"),
                            Action("elasticloadbalancing", "RegisterInstancesWithLoadBalancer"),
                            Action("elasticloadbalancing", "RegisterTargets"),
                        ],
                        Resource=[
                            "*"
                        ]
                    ),
                    Statement(
                        Effect=Allow,
                        Action=[
                            Action("logs", "CreateLogStream"),
                            Action("logs", "PutLogEvents"),
                            Action("logs", "CreateLogGroup"),
                        ],
                        Resource=[
                            Join(
                                ":",
                                [
                                    "arn:aws:logs",
                                    Ref("AWS::Region"),
                                    Ref("AWS::AccountId"),
                                    "log-group", Ref(log_group), "*"
                                ]
                            )
                        ]
                    )
                    # Statement(
                    #    Effect=Allow,
                    #    Action=[
                    #        Action("cloudwatch", "*")
                    #        ],
                    #    Resource=[
                    #        "*"
                    #    ]
                    # )
                ]
            )
        )
    ]

))

autoscale_role = t.add_resource(iam.Role(
    "AutoscaleRole",
    AssumeRolePolicyDocument=Policy(
        Version="2012-10-17",
        Statement=[
            Statement(
                Effect=Allow,
                Principal=Principal("Service", "application-autoscaling.amazonaws.com"),
                Action=[Action("sts", "AssumeRole")]
            )
        ]
    ),
    Path="/",
    Policies=[
        iam.Policy(
            PolicyName=Join("-", [Ref("AWS::StackName"), "AutoScalePolicy"]),
            PolicyDocument=Policy(
                Version="2012-10-17",
                Statement=[
                    Statement(
                        Effect=Allow,
                        Action=[
                            Action("ecs", "DescribeServices"),
                            Action("ecs", "UpdateService"),
                        ],
                        Resource=["*"]
                    ),
                    Statement(
                        Effect=Allow,
                        Action=[
                            Action("cloudwatch", "DescribeAlarms"),
                        ],
                        Resource=["*"]
                    )
                ],
            )
        )
    ]

))

"""
Create a TargetGroup to be attached to ALB of the ECS-stack
"""
target_group = t.add_resource(elasticloadbalancingv2.TargetGroup(
    "TargetGroup1",
    Port=Ref(container_port),
    Protocol="HTTP",
    HealthCheckPath=Ref(health_check_path),
    HealthCheckIntervalSeconds="30",
    HealthCheckProtocol="HTTP",
    HealthCheckTimeoutSeconds="10",
    HealthyThresholdCount="4",
    Matcher=elasticloadbalancingv2.Matcher(HttpCode="200,302"),
    UnhealthyThresholdCount="3",
    VpcId=ImportValue(Sub("${NetworkStack}-Vpc")),
    Tags=[{"Key": "TargetGroupName", "Value": Join("", ["Tg-", Ref(container_name)])}]
))

"""
Task definition
"""
task_definition = t.add_resource(ecs.TaskDefinition(
    "TaskDefinition",
    DependsOn=log_group.title,
    TaskRoleArn=GetAtt(task_role, "Arn"),
    NetworkMode="bridge",
    Family=Ref(family),
    ContainerDefinitions=[
        ecs.ContainerDefinition(
            LogConfiguration=
            ecs.LogConfiguration(
                LogDriver="awslogs",
                Options={
                    "awslogs-group": Ref("AWS::StackName"),
                    "awslogs-region": Ref("AWS::Region"),
                    "awslogs-stream-prefix": Ref(container_name)
                }
            ),
            Memory=1536,
            PortMappings=[
                ecs.PortMapping(
                    HostPort=0,
                    ContainerPort=Ref(container_port),
                    Protocol="tcp"
                ),
            ],
            Essential=True,
            # Command="/aws-start.sh",
            EntryPoint=[
                "/usr/local/ncbin/getenv.sh",
            ],
            Name=Ref(container_name),
            Image=Join("", [
                Ref(ecr), "/",
                Ref(image_name), ":",
                Ref(image_tag)
            ]),
            Cpu=200,
            MemoryReservation=500,
            Environment=[
                ecs.Environment(
                    Name="REALENTRYPOINT",
                    Value=Ref(real_entrypoint)
                ),
                ecs.Environment(
                    Name="AWSStackName",
                    Value=Ref("AWS::StackName")
                ),
                ecs.Environment(
                    Name="AWSRegion",
                    Value=Ref("AWS::Region")
                ),
                ecs.Environment(
                    Name="SCR_HOST",
                    Value=Ref(scr_hostname)
                ),
                ecs.Environment(
                    Name="ENV",
                    Value="staging"
                ),
                ecs.Environment(
                    Name="HTTP_HOSTNAME",
                    Value=ImportValue(Sub("${EcsStack}-AppLbPublicDNSName"))
                )
            ],
            MountPoints=[
                ecs.MountPoint(
                    ContainerPath="/usr/local/ncbin",
                    SourceVolume="usrlocalbin",
                    ReadOnly=True
                ),
                ecs.MountPoint(
                    ContainerPath="/var/www/cache",
                    SourceVolume="data",
                    ReadOnly=False
                ),
            ]
        )
    ],
    Volumes=[
        ecs.Volume(
            Host=ecs.Host(
                SourcePath="/usr/local/bin/"
            ),
            Name="usrlocalbin",
        ),
        ecs.Volume(
            Host=ecs.Host(
                SourcePath="/mnt/persistent/platform/"
            ),
            Name="data",
        ),
    ]
))

"""
Add the TargetGroup to a Listener on the ALB
 - path-pattern is given as a Parameter to this stack
"""
listener_rule1 = t.add_resource(elasticloadbalancingv2.ListenerRule(
    "ListenerRule1",
    Actions=[
        elasticloadbalancingv2.Action(
            TargetGroupArn=Ref(target_group),
            Type="forward"
        )
    ],
    Conditions=[
        elasticloadbalancingv2.Condition(
            Field="path-pattern",
            Values=[Ref(service_path)]
        ),
        If(service_host_condition,
           elasticloadbalancingv2.Condition(
               Field="host-header",
               Values=[Ref(service_host)]
           ),
           Ref("AWS::NoValue")
           )
    ],
    ListenerArn=ImportValue(Sub("${EcsStack}-AppLbListenerPublic80")),
    Priority=Ref(listener_priority)
))

listener_rule2 = t.add_resource(elasticloadbalancingv2.ListenerRule(
    "ListenerRule2",
    Actions=[
        elasticloadbalancingv2.Action(
            TargetGroupArn=Ref(target_group),
            Type="forward"
        )
    ],
    Conditions=[
        elasticloadbalancingv2.Condition(
            Field="path-pattern",
            Values=[Ref(service_path)]
        ),
        If(service_host_condition,
           elasticloadbalancingv2.Condition(
               Field="host-header",
               Values=[Ref(service_host)]
           ),
           Ref("AWS::NoValue")
           )
    ],
    ListenerArn=ImportValue(Sub("${EcsStack}-AppLbListenerPublic443")),
    Priority=Ref(listener_priority)
))

# Allow NAT instances to access Public ALB
sg_alb_public_ingress_rules80 = {}
sg_alb_public_ingress_rules443 = {}
for az in ["A","B","C"]:
    sg_alb_public_ingress_rules80[az] = t.add_resource(
        ec2.SecurityGroupIngress(
            "CtaPlatformIngressRule"+az,
            CidrIp=Join("/", [ImportValue(Sub("${NetworkStack}-NatIpPublic"+az)), "32"]),
            IpProtocol="6",
            FromPort=80,
            ToPort=80,
            GroupId=ImportValue(Sub("${EcsStack}-SgAlbPublicGroupId"))
        )
    )
    sg_alb_public_ingress_rules443[az] = t.add_resource(
        ec2.SecurityGroupIngress(
            "CtaPlatformIngressRuleSsl"+az,
            CidrIp=Join("/", [ImportValue(Sub("${NetworkStack}-NatIpPublic"+az)), "32"]),
            IpProtocol="6",
            FromPort=443,
            ToPort=443,
            GroupId=ImportValue(Sub("${EcsStack}-SgAlbPublicGroupId"))
        )
    )

"""
Service definition
 - Spread to several AZs for HA
 - Binpack to minimize number of required hosts per AZ
"""
service = t.add_resource(ecs.Service(
    "Service",
    Cluster=ImportValue(Sub("${EcsStack}-Cluster")),
    DependsOn=service_role,
    DesiredCount=2,
    LoadBalancers=[
        ecs.LoadBalancer(
            ContainerName=Ref(container_name),
            ContainerPort=Ref(container_port),
            TargetGroupArn=Ref(target_group)
        ),
    ],
    PlacementStrategies=[
        ecs.PlacementStrategy(
            Type="spread",
            Field="attribute:ecs.availability-zone"
        ),
        ecs.PlacementStrategy(
            Type="binpack",
            Field="memory"
        ),
    ],
    Role=Ref(service_role),
    TaskDefinition=Ref(task_definition),
    DeploymentConfiguration=ecs.DeploymentConfiguration(
        MaximumPercent="100",
        MinimumHealthyPercent="50"
    ),
))

"""
Make the service a ScalableTarget
"""
# scalable_target = t.add_resource(applicationautoscaling.ScalableTarget(
#     'ScalableTarget',
#     MaxCapacity=Ref(autoscaling_max),
#     MinCapacity=Ref(autoscaling_min),
#     ResourceId=Join("/", [
#         "service",
#         ImportValue(Sub("${EcsStack}-Cluster")),
#         GetAtt(service, "Name")
#     ]),
#     RoleARN=GetAtt(autoscale_role, "Arn"),
#     ScalableDimension='ecs:service:DesiredCount',
#     ServiceNamespace='ecs',
# ))
#
# """
# Scale out/in policies
#  - Scale out +50%, scale in -1
# """
# service_scale_out_policy = t.add_resource(applicationautoscaling.ScalingPolicy(
#     'ServiceScaleOutPolicy',
#     PolicyName='ServiceScaleOutPolicy',
#     PolicyType='StepScaling',
#     ScalingTargetId=Ref(scalable_target),
#     StepScalingPolicyConfiguration=applicationautoscaling.StepScalingPolicyConfiguration(
#         AdjustmentType='PercentChangeInCapacity',
#         Cooldown=300,
#         MetricAggregationType='Average',
#         StepAdjustments=[
#             applicationautoscaling.StepAdjustment(
#                 MetricIntervalLowerBound=0,
#                 ScalingAdjustment=50,
#             ),
#         ],
#     ),
# ))
#
# service_scale_in_policy = t.add_resource(applicationautoscaling.ScalingPolicy(
#     'ServiceScaleInPolicy',
#     PolicyName='ServiceScaleInPolicy',
#     PolicyType='StepScaling',
#     ScalingTargetId=Ref(scalable_target),
#     StepScalingPolicyConfiguration=applicationautoscaling.StepScalingPolicyConfiguration(
#         AdjustmentType='ChangeInCapacity',
#         Cooldown=300,
#         MetricAggregationType='Average',
#         StepAdjustments=[
#             applicationautoscaling.StepAdjustment(
#                 MetricIntervalUpperBound=0,
#                 ScalingAdjustment=-1,
#             ),
#         ],
#     ),
# ))
#
# """
# CloudWatch Alarms
#  - Thresholds for scaling service out/in based on CPU usage
# """
#
# service_cpu_alarm_high = t.add_resource(cloudwatch.Alarm(
#     'ServiceCpuAlarmHigh',
#     AlarmDescription='Scale out if avg CPU usage of a service is >70% for 2 minutes',
#     Namespace='AWS/ECS',
#     Dimensions=[cloudwatch.MetricDimension(
#         Name="ServiceName",
#         Value=GetAtt(service, "Name")
#     ),
#         cloudwatch.MetricDimension(
#             Name="ClusterName",
#             Value=ImportValue(Sub("${EcsStack}-Cluster"))
#         )],
#     MetricName='CPUUtilization',
#     Statistic='Average',
#     Period='60',
#     EvaluationPeriods='2',
#     Threshold='70',
#     ComparisonOperator='GreaterThanThreshold',
#     AlarmActions=[Ref(service_scale_out_policy)],
# ))
#
# service_cpu_alarm_low = t.add_resource(cloudwatch.Alarm(
#     'ServiceCpuAlarmLow',
#     AlarmDescription='Scale in if avg CPU usage of a service is <25% for 20 minutes',
#     Namespace='AWS/ECS',
#     Dimensions=[cloudwatch.MetricDimension(
#         Name="ServiceName",
#         Value=GetAtt(service, "Name")
#     ),
#         cloudwatch.MetricDimension(
#             Name="ClusterName",
#             Value=ImportValue(Sub("${EcsStack}-Cluster"))
#         )],
#     MetricName='CPUUtilization',
#     Statistic='Average',
#     Period='60',
#     EvaluationPeriods='20',
#     Threshold='25',
#     ComparisonOperator='LessThanThreshold',
#     AlarmActions=[Ref(service_scale_in_policy)],
# ))
t.add_resource(ssm.Parameter(
    container_port.title + "Ref",
    Name=Sub("/cfn/${AWS::StackName}/" + container_port.title + "/Ref"),
    Value=Ref(container_port),
    Type="String"
))

print(t.to_json())
