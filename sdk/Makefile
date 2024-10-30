.PHONY: build 

BUILD=`date +%y%m%d`

build: clean
	mkdir -p build/mobika-sdk
	cp -a *.php src examples build/mobika-sdk
	cd build && zip -r mobika-sdk-php-$(BUILD).zip mobika-sdk

clean:
	rm -rf build
