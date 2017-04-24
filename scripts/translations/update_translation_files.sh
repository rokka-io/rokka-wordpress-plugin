HERE=`dirname $0`
ROOT="$HERE/../.."
msgmerge -U "$ROOT/languages/rokka-integration-de_CH.po" "$ROOT/languages/rokka-integration.pot"
msgmerge -U "$ROOT/languages/rokka-integration-de_DE.po" "$ROOT/languages/rokka-integration.pot"
