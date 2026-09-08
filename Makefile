# create a zip package of the function code for deployment
# based on composer.json archive settings
create_package:
	composer update
	composer archive --format=zip --file=code