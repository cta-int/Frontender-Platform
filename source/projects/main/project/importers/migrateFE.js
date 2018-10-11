(async () => {
    // Make the connection.
    let collections = [
        "blueprints",
        "controls",
        "pages",
        "pages.public",
        "pages.trash"
    ];

    for (let collection of collections) {
        console.log(`Migrating collection: ${collection}`);

        const path = require("path"),
            Parser = require("./parsers.js"),
            config = require("dotenv").load(
                path.resolve(__dirname, "..", "..", ".env")
            ),
            Client = require("mongodb").MongoClient,
            connection = await Client.connect(
                `mongodb://${config.parsed.MONGO_HOST}:${
                    config.parsed.MONGO_PORT
                }`
            ),
            db = connection.db(config.parsed.MONGO_DB);

        let allObjects = await db
            .collection(collection)
            .find()
            .toArray();

        db.collection(collection).drop();

        allObjects = allObjects.map(object => {
            delete object._id;

            return Parser.addContainerIds(object);
        });

        db.collection(collection).insertMany(allObjects);
    }

    console.log(`All done!`);
    process.exit();
})();
