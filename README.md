# Shock Wave⚡️Radio userbot

Userbot em PHP para transmitir a programação da **Shock Wave⚡️Radio**, baseado em [MadelineProto](https://github.com/danog/MadelineProto) e [libtgvoip](https://github.com/danog/php-libtgvoip).

![Screenshot](https://github.com/jvlianodorneles/shockwaveradio/raw/master/screenshot.jpg)

Código baseado no [magnaluna](https://github.com/danog/magnaluna) e no [rdsradio](https://github.com/Gabboxl/RDSRadio).

## Instalação

```
wget https://github.com/jvlianodorneles/shockwaveradio/raw/master/shock.php
```

Não esqueça de conferir atualizações sobre as [dependências necessárias](https://docs.madelineproto.xyz/docs/REQUIREMENTS.html) para o MadelineProto.

## Instalação das dependências

### Ambiente PHP

Abaixo seguem instruções para a instalação das dependências sob as quais o funcionamento do userbot foi verificado:

```
sudo apt-get install python-software-properties software-properties-common
sudo LC_ALL=C.UTF-8 add-apt-repository ppa:ondrej/php
sudo apt-get update
sudo apt-get install php7.3 php7.3-dev php7.3-xml php7.3-zip php7.3-gmp php7.3-cli php7.3-mbstring php7.3-json git -y
```

### php-libtgvoip, ffmpeg e screen

```
sudo apt-get install ffmpeg screen libopus-dev libssl-dev build-essential php$(echo "<?php echo PHP_MAJOR_VERSION.'.'.PHP_MINOR_VERSION;" | php)-dev && git clone https://github.com/danog/PHP-CPP && cd PHP-CPP && make -j$(nproc) && sudo make install && cd .. && git clone --recursive https://github.com/danog/php-libtgvoip && cd php-libtgvoip && make && sudo make install && cd
```

## Execução

Para executar o userbot:

```
php shock.php
```

- Fica aqui meu muito obrigado por toda a ajuda da comunidade de desenvolvedores PHP. Vocês são corajosos! 😎
