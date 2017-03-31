HERE=`dirname $0`
ROOT="$HERE/../.."
for file in `find "$ROOT/lang" -name "*.po"` ; do msgfmt -o ${file/.po/.mo} $file ; done
