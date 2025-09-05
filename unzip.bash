rm -rf luxcal_src
rm -rf tmp

mkdir tmp
mkdir luxcal_src

unzip -q luxcal*.zip -d tmp
unzip -q tmp/*calendar*.zip -d luxcal_src

rm -rf tmp
