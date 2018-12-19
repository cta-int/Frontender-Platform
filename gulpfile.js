/******************/
/** Basic Imports */
/******************/

/**
 * Lets set some basic rules:
 * 1. When given on the cli --localhost then the styleguide is hosted on port 3000
 * 2. When project flags are given (prepended with "--") these projects are watched and built accordingly!
 *
 * The following tasks need to be ran on the following commands:
 * dev: move assets, move project related files, build sass, parse project related css files with postcss.
 * test: ImageOptim.
 * production: Uglify JS, Minify css/html).
 */

var gulp        = require('gulp'),
    sass        = require('gulp-sass'),
    config      = require(process.cwd() + '/config.json'),
    tasks       = [],
    server      = false,
    child_process = require('child_process'),
    fs          = require('fs'),
    projects    = process.argv.map(function(item) {
        if(item.indexOf('--') === -1) {
            return false;
        }

        item = item.replace(/-/g, '');
        if(item == 'server') {
            server = true;
            return false;
        }

        return item;
    }).filter(function(item) {
        return item;
    }),
    extras      = [
        '<script src="/javascript/modernizr-custom.js"></script>',
        '<script src="//ajax.googleapis.com/ajax/libs/jquery/1.12.4/jquery.min.js"></script>',
        '<script src="/javascript/jquery.plugins.js"></script>',
        '<script src="/javascript/jquery.modules.js"></script>',
        '<script src="/javascript/navigation.js"></script>',
        '<script src="/javascript/picturefill.min.js"></script>',
        '<script src="/javascript/fonts.js"></script>',
        '<style>.sg-navigation-section{display:block!important;}.sg.sg-wrapper,.sg.sg-header .sg-inner{max-width:100%;}</style>'
    ];

// Leak config variable to global scope.
global.config = config;
global.projects = projects;
require(process.cwd() + '/gulp-plugins');

/**
 * This task moves all the assets to the styleguide and www folders.
 */
gulp.task('assets', function() {
    return loop(projects, function(project) {
        var conf = config.assets[project];

        if(typeof conf === 'string') {
            return gulp.src(config.source + '/assets/**/*').pipe(gulp.dest(conf))
        } else if(typeof conf === 'object' && conf !== null) {
            // Loop through all the keys and create a promise out of it.
            var promises = [];

            for(var index in conf) {
                if(config.hasOwnProperty(index)) {
                    var source = index,
                        destination = conf[index];

                    promises.push(new Promise(function(resolve, reject) {
                        gulp.src(config.source + '/assets/' + directory + '/**/*')
                            .pipe(gulp.dest(destination))
                            .on('end', function() {
                                resolve(true);
                            })
                            .on('error', function(err) {
                                reject(err);
                            })
                    }));
                }
            }

            return Promise.all(promises);
        } else {
            return gulp.src(config.source + '/assets/**/*').pipe(gulp.dest(config.projects[project]))
        }
    });
});

/**
 * This task will build all the css and run the registred tasks.
 */

if(!projects.length) {
    console.log('\x1b[31m', 'Hmmmm, you missed something. (projects maybe)', '\x1b[0m');
    process.exit();
}

gulp.task('build', function() {
    var stream = gulp.src(process.cwd() +'/' + config.source + '/sass/*.scss')
        .pipe(sass())
        .pipe(gulp.dest(config.projects.styleguide + '/styles'));

    for(var index in projects) {
        if(projects.hasOwnProperty(index)) {
            var output = config.sass[projects[index]] || config.projects[projects[index]] + '/styles';

            stream.pipe(gulp.dest(output));
            gulp.start(tasks);
        }
    }

    return stream;
});

gulp.task('move', function() {
    // Move project specific files to their new home.
    for(var index in projects) {
        if(projects.hasOwnProperty(index)) {
            gulp.src(config.source + '/projects/' + projects[index] + '/**/*')
                .pipe(gulp.dest(config.projects[projects[index]]));
        }
    }
});

gulp.task('styleguide', function() {
    var styleguide = require('sc5-styleguide').generate({
        title: 'Pattern Library',
        rootPath: config.projects.styleguide,
        server: server,
        overviewPath: config.source + '/projects/styleguide/overview.md',
        sideNav: true,
        previousSection: true,
        nextSection: true,
        disableHtml5Mode: true,
        extraHead: extras
    });

    gulp.src(config.source + '/sass/**/*.scss')
        .pipe(styleguide)
        .pipe(gulp.dest(config.projects.styleguide));

    return gulp.src(config.source + '/sass/screen.scss')
        .pipe(sass())
        .pipe(require('sc5-styleguide').applyStyles())
        .pipe(gulp.dest(config.projects.styleguide));
});

/**
 * This task will watch all the sass files and build the css/ minify and build the styleguide.
 */

gulp.task('init', function() {
    return loop(projects, function(project) {
        if(!config.projects[project]) {
            return Promise.resolve(true);
        }

        try {
            fs.accessSync(config.projects[project], fs.F_OK);
            return Promise.resolve(true);
        } catch (e) {
            // Do nothing!
        }

        if(config.init[project]) {
            return new Promise(function(resolve, reject) {
                child_process.exec('composer create-project ' + config.init[project] + ' ' + config.projects[project], function(err, stdout, stderr) {
                    if(err) {
                        return reject(err);
                    }

                    return resolve(true);
                });
            })
        } else {
            return Promise.resolve(true);
        }
    });
});

gulp.task('dev', function() {
    tasks = ['autopref'];
    gulp.start(['move', 'build', 'styleguide', 'assets']);
    gulp.watch(config.source + '/**/*', ['move', 'build', 'styleguide', 'assets']);
});

gulp.task('test', function() {
    tasks = tasks.concat(['imagemin', 'concat', 'uglify', 'autopref', 'minify']);
    gulp.start(['move', 'build', 'styleguide', 'assets']);
});

gulp.task('production', function() {
    tasks = tasks.concat(['docblock', 'imagemin', 'concat', 'uglify', 'autopref', 'minify']);
    gulp.start(['move', 'build', 'styleguide', 'assets']);
});

/**
 * Current changes:
 *
 * Assets:
 * When a project has no assets description assets will be placed in an assets folder in the root of the projects destination folder.
 * When A string is given this will be the folder in which all the assets will be placed.
 * When an object is given all the folders in the object will be moved to their new destination. The destination is a folder and the file will be placed in here.
 * *) This is also to be done for the projects.
 *
 * init:
 * The init method is now currently only for composer. This will do a create project of a project.
 * This will only be executed when there is a init project and that the project folder does not exist.
 *
 * sass:
 * This is a new functionality. This will give a project and the sass destination folder.
 */
