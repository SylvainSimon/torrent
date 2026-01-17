`apt update && sudo apt install screen rsync -y`

`screen -S transfert_rsync`

`screen -r transfert_rsync`

`rsync -avP sylvanusman@cat.seedhost.eu:/home20/sylvanusman/downloads/ .`
