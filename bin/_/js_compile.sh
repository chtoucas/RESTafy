#!/bin/sh

PROJECT_DIR=$(cd `dirname $0` && pwd)/..
CLOSURE_SIMPLE='java -jar /usr/local/share/closure-compiler/compiler.jar --compilation_level SIMPLE_OPTIMIZATIONS'
CLOSURE_ADVANCED='java -jar /usr/local/share/closure-compiler/compiler.jar --compilation_level ADVANCED_OPTIMIZATIONS'

cd ${PROJECT_DIR}/ext

#yuicompressor jquery.watermark.debug.js -o jquery.watermark.js --charset utf-8 --type js

echo '-- Compiling scripts'
#echo '  jquery.js'
#cd jquery
#${CLOSURE_SIMPLE} --js jquery.debug.js --js_output_file jquery.js
#cd -
echo '  jquery.colorbox.js'
cd jquery.colorbox
${CLOSURE_SIMPLE} --js jquery.colorbox.debug.js --js_output_file jquery.colorbox.js
cd -
echo '  jquery.cookie.js'
cd jquery.cookie
${CLOSURE_SIMPLE} --js jquery.cookie.debug.js --js_output_file jquery.cookie.js
cd -
echo '  jquery.validate.js'
cd jquery.validate
${CLOSURE_SIMPLE} --js jquery.validate.debug.js --js_output_file jquery.validate.js
cd -
echo '  yepnope.js'
cd yepnope
${CLOSURE_SIMPLE} --js yepnope.debug.js --js_output_file yepnope.js
cd -

cd -
