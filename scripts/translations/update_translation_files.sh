HERE=`dirname $0`
ROOT="$HERE/../.."
msgmerge -U "$ROOT/languages/rokka-image-cdn-de_CH.po" "$ROOT/languages/rokka-image-cdn.pot"
msgmerge -U "$ROOT/languages/rokka-image-cdn-de_DE.po" "$ROOT/languages/rokka-image-cdn.pot"
