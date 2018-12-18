/**
 * This file has been created for the plugins within our gulp file.
 * This is the last thing I wanted to do but all plugins have a little differnce and I want to cope with that.
 *
 * All the plugins will now be single functions and can be called customly.
 */
var gulp = require('gulp'),
    minify = require('gulp-clean-css'),
    imagemin = require('gulp-imagemin'),
    uglify = require('gulp-uglify'),
    autopref = require('gulp-autoprefixer'),
    concat = require('gulp-concat'),
    htmlmin = require('gulp-htmlmin'),
    through2 = require('through2');

global.loop = function(array, callback) {
    var promises = [];

    for(var index in array) {
        if(array.hasOwnProperty(index)) {
            var stream = callback(array[index]);

            if(stream instanceof Promise) {
                promises.push(stream)
            } else {
                promises.push(new Promise(function(resolve, reject) {
                    stream.on('end', function() {
                        return resolve(true);
                    });
                    stream.on('error', function(err) {
                        return reject(err);
                    });
                }));
            }
        }
    }

    return Promise.all(promises);
};

function filesArray(output, files) {
    if(Array.isArray(files)) {
        return files.map(function(item) {
            return output + '/' + item;
        });
    }

    return output + '/' + files;
}

function skipDocblock() {
    return through2.obj(function(file, encoding, callback) {
        var name = require('path').basename(file.path);

        if(name.indexOf('docblock') >= 0) {
            return callback(null);
        }

        return callback(null, file);
    });
}

gulp.task('concat', ['assets'], function(done) {
    return loop(projects, function(project) {
        var config = global.config.plugins.concat,
            output = global.config.projects[project];

        return gulp.src(output + '/' + config.files)
            .pipe(concat(config.config))
            .pipe(gulp.dest(output));
    });
});

gulp.task('uglify', ['concat'] ,function() {
    return loop(projects, function(project) {
        var config = global.config.plugins.uglify,
            output = global.config.projects[project];

        return gulp.src(output + '/' + config.files)
            .pipe(skipDocblock())
            .pipe(uglify())
            .pipe(gulp.dest(output + '/javascript'));
    });
});

gulp.task('imagemin', ['assets'], function() {
    return loop(projects, function(project) {
        var config = global.config.plugins.imagemin,
            output = global.config.projects[project],
            folders = config.files.map(function(item) {
                return output + '/' + item;
            });

        return gulp.src(folders)
            .pipe(imagemin(config.config))
            .pipe(gulp.dest(output));
    });
});

gulp.task('autopref', ['build'], function() {
    return loop(projects, function(project) {
        var config = global.config.plugins.autopref,
            output = global.config.projects[project];

        return gulp.src(output + '/' + config.files)
            .pipe(autopref(config.config))
            .pipe(gulp.dest(output));
    });
});

gulp.task('minify', ['build', 'autopref', 'assets'], function() {
    return loop(projects, function(project) {
        var config = global.config.plugins.minify,
            output = global.config.projects[project];

        return gulp.src(output + '/' + config.files)
            .pipe(skipDocblock())
            .pipe(minify(config.config))
            .pipe(gulp.dest(output));
    });
});

gulp.task('htmlmin', ['move'], function() {
    return loop(projects, function(project) {
        var config = global.config.plugins.htmlmin,
            output = global.config.projects[project];

        return gulp.src(output + '/' + config.files)
            .pipe(skipDocblock())
            .pipe(htmlmin(config.config))
            .pipe(gulp.dest(output));
    });
});

gulp.task('docblock', ['assets', 'minify', 'uglify', 'move'], function() {
    return loop(projects, function(project) {
        var config = global.config.plugins.docblock,
            output = global.config.projects[project],
            files = filesArray(output, config.files);

        return gulp.src(files)
            .pipe(through2.obj(function(file, encoding, callback) {
                var extension = require('path').extname(file.path),
                    path = process.cwd() + '/' + output + '/docblock' + extension,
                    docblock = require('fs').existsSync(path),
                    name = require('path').basename(file.path, extension);

                if(!docblock || name === 'docblock') {
                    return callback(null, file);
                }

                var buffer = Buffer.from(require('fs').readFileSync(path, 'utf8'));

                file.contents = Buffer.concat([buffer, file.contents], buffer.length + file.contents.length);
                return callback(null, file);
            }))
            .pipe(gulp.dest(output))
            .on('end', function() {
                for(var index in config.files) {
                    if(config.files.hasOwnProperty(index)) {
                        var extension = require('path').extname(config.files[index]),
                            path = process.cwd() + '/' + output + '/docblock' + extension,
                            docblock = require('fs').existsSync(path);

                        if (docblock) {
                            require('fs').unlinkSync(path);
                        }
                    }
                }
            });
    });
});