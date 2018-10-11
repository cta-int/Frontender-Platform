var fs = require('fs'),
    path = require('path'),
    mkdirp = require('mkdirp'),
    pages = require('./pages-temp.json');

try {
    for (var page of pages.pages) {
        // We will unset some parts of the page and we will remember some.
        let published = page.publish,
            route = page.route,
            file = `./temp_pages/${published ? 'published' : 'unpublished'}/${route}.json`;

        delete page.publish;
        delete page.route;
        delete page.thumbnail;

        mkdirp.sync(path.dirname(file));
        fs.writeFileSync(file, JSON.stringify(page));
    }
} catch(err) {
    console.log(err);
}

console.log('Done');