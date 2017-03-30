var gulp = require('gulp'),
    sass = require('gulp-sass'),
    sourcemaps = require('gulp-sourcemaps'),
    autoprefixer = require('gulp-autoprefixer'),
    rename = require('gulp-rename'),
    uglify = require('gulp-uglify');

gulp.task('default', function () {});

gulp.task('styles', function () {
    return gulp.src('./assets/scss/admin.scss')
        .pipe(sourcemaps.init())
        .pipe(sass({
            outputStyle: 'compressed'
        }).on('error', sass.logError))
        .pipe(autoprefixer('last 2 version'))
        .pipe(sourcemaps.write('.'))
        .pipe(gulp.dest('./assets/css'));
});

gulp.task('scripts', function () {
    return gulp.src([
        './assets/js/admin.js',
        './assets/js/settings.js'
    ])
        .pipe(uglify())
        .pipe(rename({ suffix: '.min' }))
        .pipe(gulp.dest('./assets/js/'));
});

gulp.task('deploy', ['styles', 'scripts']);
