(async () => {
    const path = require('path'),
          fs = require('fs'),
          Parser = require('./../parsers.js'),
          config = require('dotenv').load(path.resolve(__dirname, '..', '..', '..', '.env')),
          Client = require('mongodb').MongoClient,
          connection = await Client.connect(`mongodb://${config.parsed.MONGO_HOST}:${config.parsed.MONGO_PORT}`),
          db = connection.db(config.parsed.MONGO_DB),
          pages = require('glob').sync(path.resolve(__dirname, '..', '..', 'blueprints', 'pages', '**', '*.json')),
          collection = db.collection('blueprints');

    let promises = pages.map((path) => {
        return Promise.resolve(JSON.parse(fs.readFileSync(path)))
            .then(Parser.moveContainersOutsideConfig.bind(Parser))
            .then(Parser.addAdapterToModelConfig.bind(Parser))
            .then(Parser.localizeTranslations.bind(Parser))
            .then(Parser.addDefinition.bind(Parser))
            .then((page) => {
                page.revision.type = 'page';
                return page;
            })
            .then(collection.insertOne.bind(collection));
    });

    await Promise.all(promises);

    connection.close();
})();