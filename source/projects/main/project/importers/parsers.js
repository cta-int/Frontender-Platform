const crypto = require("crypto");
const inflected = require("inflected");

module.exports = class Parser {
    static moveContainersOutsideConfig(data) {
        if (data.hasOwnProperty("template_config")) {
            if (data.template_config.hasOwnProperty("containers")) {
                data.containers = data.template_config.containers.splice(0);
                delete data.template_config.containers;
            }
        }

        if (data.hasOwnProperty("containers")) {
            for (let index in data.containers) {
                if (data.containers.hasOwnProperty(index)) {
                    data.containers[index] = this.moveContainersOutsideConfig(
                        data.containers[index]
                    );
                }
            }
        }

        return data;
    }

    static localizeTranslations(data) {
        // I have to check every object here.
        // If it contains "en" it will be moved to "en-GB",
        // "nl" => "nl-NL",
        // "fr" => "fr-FR".

        for (let index in data) {
            if (data.hasOwnProperty(index) && data[index] !== null) {
                if (data[index].hasOwnProperty("en")) {
                    data[index]["en-GB"] = data[index]["en"];
                    delete data[index]["en"];
                }

                if (data[index].hasOwnProperty("fr")) {
                    data[index]["fr-FR"] = data[index]["fr"];
                    delete data[index]["fr"];
                }

                if (data[index].hasOwnProperty("nl")) {
                    data[index]["nl-NL"] = data[index]["nl"];
                    delete data[index]["nl"];
                }

                if (data[index].hasOwnProperty("pt")) {
                    data[index]["pt-PT"] = data[index]["pt"];
                    delete data[index]["pt"];
                }

                if (data[index].hasOwnProperty("es")) {
                    data[index]["es-ES"] = data[index]["es"];
                    delete data[index]["es"];
                }

                if (data[index] instanceof Object) {
                    data[index] = this.localizeTranslations(data[index]);
                }
            }
        }

        return data;
    }

    static addAdapterToModelConfig(data) {
        if (data.hasOwnProperty("template_config")) {
            if (data.template_config.hasOwnProperty("model")) {
                // Check if we have all the data required for this.
                let model = data.template_config.model,
                    dataSection = {
                        control: "project/model/adapter",
                        value: {
                            adapter: "SCR"
                        }
                    };

                if (model.controls.hasOwnProperty('name') && model.controls.name.value) {
                    let modelName = model.controls.name.value
                        .split("\\")[0]
                        .toLowerCase();
                    dataSection.control = `project/model/${inflected.pluralize(
                        modelName
                    )}`;
                    dataSection.value.model = modelName;

                    delete data.template_config.model.controls.name;
                }

                if (model.controls.hasOwnProperty("id")) {
                    if (dataSection.value.model !== "media") {
                        dataSection.control = `project/model/${inflected.singularize(
                            dataSection.value.model
                        )}`;
                    }
                    dataSection.value.id = model.controls.id.value;

                    delete data.template_config.model.controls.id;
                }

                delete data.template_config.model.controls.adapter;
                data.template_config.model.controls.data = dataSection;
            }
        }

        if (data.hasOwnProperty("containers")) {
            for (let index in data.containers) {
                if (data.containers.hasOwnProperty(index)) {
                    data.containers[index] = this.addAdapterToModelConfig(
                        data.containers[index]
                    );
                }
            }
        }

        return data;
    }

    static addDefinition(data) {
        let clone = {
            revision: {
                date: new Date().toISOString(),
                user: {},
                hash: crypto
                    .createHash("md5")
                    .update(JSON.stringify(data))
                    .digest("hex")
            },
            definition: data
        };

        return clone;
    }

    static async sanitizePage(page, db) {
        let container = page.hasOwnProperty("definition")
            ? page.definition
            : page;

        if (
            !container.hasOwnProperty("template_config") &&
            container.hasOwnProperty("blueprint")
        ) {
            let blueprint = await db.collection("blueprints").findOne({
                _id: page.blueprint
            });

            container["frontender"] = blueprint.definition["frontender"];
            container.template = blueprint.definition.template;
            container.template_config = JSON.parse(
                JSON.stringify(blueprint.definition.template_config)
            );
        }

        if (container.hasOwnProperty("template_config")) {
            let newConfig = {};

            for (let index in container.template_config) {
                if (container.template_config.hasOwnProperty(index)) {
                    let section = container.template_config[index],
                        newSection = {};

                    if (section.hasOwnProperty("controls")) {
                        for (let idx in section["controls"]) {
                            let control = section["controls"][idx];

                            if (control["value"]) {
                                newSection[idx] = control["value"];
                            }

                            newConfig[index] = newSection;
                        }
                    }
                }
            }

            container.template_config = newConfig;
        }

        if (container.hasOwnProperty("containers")) {
            for (let index in container.containers) {
                container.containers[index] = await this.sanitizePage(
                    container.containers[index],
                    db
                );
            }
        }

        return page;
    }

    static addContainerIds(page) {
        let container = page.hasOwnProperty("definition")
            ? page.definition
            : page;

        if (container.hasOwnProperty("fe-id")) {
            delete container["fe-id"];
        }

        container["frontender"] = crypto.randomBytes(16).toString("hex");
        if (container.hasOwnProperty("containers")) {
            for (let index in container.containers) {
                container.containers[index] = this.addContainerIds(
                    container.containers[index]
                );
            }
        }

        return page;
    }
};
