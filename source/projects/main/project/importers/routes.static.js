(async () => {
    let Client = require("mongodb").MongoClient,
        path = require("path"),
        config = require("dotenv").load(
            path.resolve(__dirname, "..", "..", ".env")
        ),
        connection = await Client.connect(config.parsed.MONGO_HOST),
        db = connection.db(config.parsed.MONGO_DB),
        oldRoutes = await db
            .collection("routes.static")
            .find()
            .toArray(),
        newRoutesCollection = db.collection("routes");

    let promises = oldRoutes.map(async route => {
        route.resource = route.source;
        route.type = "landingpage";
        route.status = 302;

        delete route.source;

        return newRoutesCollection.insertOne(route);
    });

    await Promise.all(promises);

    connection.close();
})();
