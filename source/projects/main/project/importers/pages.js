(async () => {
    const path = require("path"),
        fs = require("fs"),
        Parser = require("./parsers.js"),
        config = require("dotenv").load(
            path.resolve(__dirname, "..", "..", ".env")
        ),
        Client = require("mongodb").MongoClient,
        connection = await Client.connect(
            `mongodb://${config.parsed.MONGO_HOST}:${config.parsed.MONGO_PORT}`
        ),
        db = connection.db(config.parsed.MONGO_DB),
        basePath = path.resolve(__dirname, "..", "pages", "published"),
        pages = require("glob").sync(`${basePath}/**/*.json`),
        collection = db.collection("pages"),
        crypto = require("crypto"),
        publicCollection = db.collection("pages.public");

    console.log(basePath);
    console.log(pages.length);

    let promises = pages.map(path => {
        return Promise.resolve(JSON.parse(fs.readFileSync(path)))
            .then(Parser.moveContainersOutsideConfig.bind(Parser))
            .then(Parser.addAdapterToModelConfig.bind(Parser))
            .then(Parser.localizeTranslations.bind(Parser))
            .then(Parser.addDefinition.bind(Parser))
            .then(Parser.addContainerIds.bind(Parser))
            .then(page => {
                page.revision.lot = crypto.randomBytes(16).toString("hex");
                page.definition.route = {
                    "en-GB": path
                        .replace(`${basePath}/`, "")
                        .replace(".json", ""),
                    "fr-FR": path
                        .replace(`${basePath}/`, "")
                        .replace(".json", "")
                };

                if (page.definition.route["en-GB"] === "home") {
                    page.definition.route["en-GB"] = "/";
                    page.definition.route["fr-FR"] = "/";
                }

                return Promise.all([
                    collection.insertMany([
                        JSON.parse(JSON.stringify(page)),
                        JSON.parse(JSON.stringify(page))
                    ]),
                    Parser.sanitizePage(
                        JSON.parse(JSON.stringify(page)),
                        db
                    ).then(publicCollection.insertOne.bind(publicCollection))
                ]);
            });
    });

    await Promise.all(promises);

    connection.close();
})();
