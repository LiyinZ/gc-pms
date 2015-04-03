// Include gulp
var gulp = require('gulp'), 

// Include Our Plugins
    jshint = require('gulp-jshint'),
    sass = require('gulp-sass'),
    minifyCSS = require('gulp-minify-css'),
    concat = require('gulp-concat'),
    uglify = require('gulp-uglify'),
    rename = require('gulp-rename');

// Lint Task
gulp.task('lint', function() {
    return gulp.src('assets/js/*.js')
        .pipe(jshint())
        .pipe(jshint.reporter('default'));
});

// Compile Our Sass
gulp.task('sass', function() {
    return gulp.src('assets/scss/style.scss')
        .pipe(sass())
        .pipe(gulp.dest('assets/css'));
});

// Compile Minified Sass
gulp.task('mcss', function() {
   return gulp.src('assets/scss/style.scss')
       .pipe(sass())
       .pipe(minifyCSS())
       .pipe(gulp.dest('assets/css'));
});

// Concatenate & Minify JS
gulp.task('js', function() {
    return gulp.src(['assets/js/init.js'])
        .pipe(concat('all.js'))
        .pipe(uglify())
        .pipe(gulp.dest('assets/js'));
});

// Watch Files For Changes
gulp.task('watch', function() {
    gulp.watch('assets/js/init.js', ['js']);
    gulp.watch([
        'assets/scss/*.scss',
        'assets/scss/base/*.scss',
        'assets/scss/layout/*.scss',
        'assets/scss/module/*.scss',
    ], ['sass']);
});

// Default Task
gulp.task('default', ['sass', 'js', 'watch']);