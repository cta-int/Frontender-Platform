(async () => {
    const path = require('path'),
          fs = require('fs'),
          Parser = require('./../parsers.js'),
          config = require('dotenv').load(path.resolve(__dirname, '..', '..', '..', '.env')),
          Client = require('mongodb').MongoClient,
          connection = await Client.connect(`mongodb://${config.parsed.MONGO_HOST}:${config.parsed.MONGO_PORT}`),
          db = connection.db(config.parsed.MONGO_DB),
          blueprints = require('glob').sync(path.resolve(__dirname, '..', '..', 'blueprints', 'containers', '**', '*.json')),
          collection = db.collection('blueprints');

    let promises = blueprints.map((filePath) => {
        return Promise.resolve(JSON.parse(fs.readFileSync(filePath)))
            .then(Parser.moveContainersOutsideConfig.bind(Parser))
            .then(Parser.addAdapterToModelConfig.bind(Parser))
            .then(Parser.localizeTranslations.bind(Parser))
            .then(Parser.addDefinition.bind(Parser))
            .then((blueprint) => {
                let parts = path.parse(filePath);
                parts.ext = '.png';

                if(fs.existsSync(path.format(parts))) {
                    blueprint.revision.thumbnail = {};
                    blueprint.revision.thumbnail['nl-NL'] = 'data:image/png;base64,' + fs.readFileSync(path.format(parts), {
                        encoding: 'base64'
                    }).toString();
                }

                blueprint.revision.type = 'container';
                return blueprint;
            })
            .then(collection.insertOne.bind(collection));
    });

    await Promise.all(promises);

    connection.close();
})();