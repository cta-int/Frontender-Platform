(async () => {
    let fs = require("fs"),
        path = require("path"),
        glob = require("glob"),
        config = require("dotenv").load(
            path.resolve(__dirname, "..", "..", ".env")
        ),
        Client = require("mongodb").MongoClient,
        connection = await Client.connect(
            `mongodb://${config.parsed.MONGO_HOST}:${config.parsed.MONGO_PORT}`
        ),
        db = connection.db(config.parsed.MONGO_DB),
        sites = ;
})();
