(async () => {
    let Client = require("mongodb").MongoClient,
        path = require("path"),
        config = require("dotenv").load(
            path.resolve(__dirname, "..", "..", ".env")
        ),
        connection = await Client.connect(config.parsed.MONGO_HOST),
        db = connection.db(config.parsed.MONGO_DB),
        redirects = require(path.resolve(__dirname, "..", "redirects.json")),
        trim = (string, charList = "\\/") => {
            return string
                .toString()
                .replace(new RegExp(`^[${charList}]+`), "")
                .replace(new RegExp(`[${charList}]+$`), "");
        };

    if (process.env.DEBUG) {
        try {
            await db.collection("routes").deleteMany({
                type: "simple"
            });
            await db.collection("routes").deleteMany({
                type: "regex"
            });
        } catch (err) {}
    }

    let newRoutesCollection = db.collection("routes");

    // All current redirects are for cta (eg www.cta.int), so these will be added to the urls.
    // No protocol will be added in this case, because the platform will do this for us.
    if (redirects.hasOwnProperty("static")) {
        for (let index in redirects.static) {
            let destination = redirects.static[index];

            if (destination.indexOf("http") !== 0) {
                destination = `www.cta.int/${trim(destination)}`;
            }

            newRoutesCollection.insertOne({
                resource: `www.cta.int/${trim(index)}`,
                destination: destination,
                type: "simple",
                status: 301
            });
        }
    }

    if (redirects.hasOwnProperty("dynamic")) {
        for (let index in redirects.dynamic) {
            let destination = redirects.dynamic[index];

            if (destination.indexOf("http") !== 0) {
                destination = `www.cta.int/${trim(destination)}`;
            }

            newRoutesCollection.insertOne({
                resource: `/www\\.cta\\.int\\/${trim(index, "\\^\\$\\/\\\\")}/`,
                destination: destination,
                type: "regex",
                status: 301
            });
        }
    }

    connection.close();
})();
