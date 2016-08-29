NAME=wp-sacloud-webaccel

default: clean all

all: 
	mkdir -p $(NAME)
	cp -r $(NAME).php wp-cli.php readme.txt screenshot-1.png lang script style tpl $(NAME)
	zip -vr $(NAME).zip $(NAME)

clean:
	rm -rf $(NAME) $(NAME).zip
