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

    if (process.env.DEBUG) {
        try {
            await db.collection("routes").deleteMany({
                type: "landingpage"
            });
        } catch (err) {}
    }

    let promises = oldRoutes.map(async route => {
        let page = await db.collection("pages.public").findOne({
            _id: require("mongodb").ObjectID(route.page_id)
        });

        route.resource = route.source;
        route.type = "landingpage";
        route.status = 302;

        if (page) {
            route.page_lot = page.revision.lot || null;
        }

        delete route.source;

        return newRoutesCollection.insertOne(route);
    });

    await Promise.all(promises);

    connection.close();
})();
