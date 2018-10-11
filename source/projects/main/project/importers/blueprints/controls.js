(async () => {
    const path = require('path'),
        fs = require('fs'),
        Parser = require('./../parsers.js'),
        config = require('dotenv').load(path.resolve(__dirname, '..', '..', '..', '.env')),
        Client = require('mongodb').MongoClient,
        connection = await Client.connect(`mongodb://${config.parsed.MONGO_HOST}:${config.parsed.MONGO_PORT}`),
        db = connection.db(config.parsed.MONGO_DB),
        projectControls = require('glob').sync(path.resolve(__dirname, '..', '..', 'blueprints', 'controls', '**', '*.json')),
        coreControls = require('glob').sync(path.resolve(__dirname, '..', '..', '..', 'core', 'blueprints', 'controls', '**', '*.json')),
        controls = projectControls.concat(coreControls),
        collection = db.collection('controls'),
        rootDir = path.resolve(__dirname, '..', '..', '..');

    let promises = controls.map((directory) => {
        return Promise.resolve(JSON.parse(fs.readFileSync(directory)))
            .then(Parser.localizeTranslations.bind(Parser))
            .then(Parser.addDefinition.bind(Parser))
            .then(function(control) {
                let dir = directory.replace(`${rootDir}/`, '').replace('blueprints/controls/', ''),
                    dirName = path.parse(dir);

                control.identifier = `${dirName.dir}/${dirName.name}`

                return control;
            })
            .then(collection.insertOne.bind(collection));
    });

    await Promise.all(promises);

    connection.close();
})();