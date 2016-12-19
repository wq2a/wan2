all:
	curl http://localhost/wan2/index.php
	curl -H "X-AUTH-TOKEN: victoria:randomsecret" http://localhost/wan2/index.php
	curl -H "X-AUTH-TOKEN: wrongusername:randomsecret" http://localhost/wan2/index.php
	curl -H "X-AUTH-TOKEN: victoria:wrongpassword" http://localhost/wan2/index.php

ch:
	sudo chgrp -R _www .
