#!/usr/bin/env php
<?php
/* Copyright 2016-2019 Daniil Gentili
 * (https://daniil.it)
 * This file is part of MadelineProto.
 * MadelineProto is free software: you can redistribute it and/or modify it under the terms of the GNU Affero General Public License as published by the Free Software Foundation, either version 3 of the License, or (at your option) any later version.
 * MadelineProto is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
 * See the GNU General Public License for more details.
 * You should have received a copy of the GNU General Public License along with MadelineProto.
 * If not, see <http://www.gnu.org/licenses/>.
 */

define('MADELINE_BRANCH', ''); // Para utilizar a versão alpha do MadelineProto.

if (\file_exists('vendor/autoload.php')) {
    require 'vendor/autoload.php';
} else {
    if (!\file_exists('madeline.php')) {
        \copy('https://phar.madelineproto.xyz/madeline.php', 'madeline.php');
    }
    include 'madeline.php';
}

if (!\file_exists('songs.php')) {
    \copy('https://github.com/danog/magnaluna/raw/master/songs.php', 'songs.php');
}

echo 'Deserializing MadelineProto from session.madeline...'.PHP_EOL;

use danog\MadelineProto\Loop\Impl\ResumableSignalLoop;

class MessageLoop extends ResumableSignalLoop
{
    const INTERVAL = 1;
    private $timeout;
    private $call;

    public function __construct($API, $call, $timeout = self::INTERVAL)
    {
        $this->API = $API;
        $this->call = $call;
        $this->timeout = $timeout;
    }

    public function loop()
    {
        $MadelineProto = $this->API;
        $logger = &$MadelineProto->logger;

        while (true) {
            $result = yield $this->waitSignal($this->pause($this->timeout));
            if ($result) {
                $logger->logger("Got signal in $this, exiting");

                return;
            }

            // try {
            //     if ($MadelineProto->jsonmoseca != $MadelineProto->nowPlaying('jsonclear')) { //anti-floodwait

            //         yield $MadelineProto->messages->editMessage(['id' => $this->call->mId, 'peer' => $this->call->getOtherID(), 'message' => 'Você está ouvindo: <b>'.$MadelineProto->nowPlaying()[1].'</b>  '.$MadelineProto->nowPlaying()[2].'<br>Tipo: <i>'.$MadelineProto->nowPlaying()[0].'</i>', 'parse_mode' => 'html']);
            //         //anti-floodwait
            //         $MadelineProto->jsonmoseca = $MadelineProto->nowPlaying('jsonclear');
            //     }
            // } catch (\danog\MadelineProto\Exception | \danog\MadelineProto\RPCErrorException $e) {
            //     $logger->logger($e);
            // }
        }
    }

    public function __toString(): string
    {
        return 'VoIP message loop '.$this->call->getOtherId();
    }
}

class StatusLoop extends ResumableSignalLoop
{
    const INTERVAL = 2;
    private $timeout;
    private $call;
    public function __construct($API, $call, $timeout = self::INTERVAL)
    {
        $this->API = $API;
        $this->call = $call;
        $this->timeout = $timeout;
    }
    public function loop()
    {
        $MadelineProto = $this->API;
        $logger = &$MadelineProto->logger;
        $call = $this->call;

        while (true) {
            $result = yield $this->waitSignal($this->pause($this->timeout));
            if ($result) {
                $logger->logger("Got signal in $this, exiting");
                return;
            }

            if ($call->getCallState() === \danog\MadelineProto\VoIP::CALL_STATE_ENDED) {
                try {
                    yield $MadelineProto->messages->sendMessage(['no_webpage' => true, 'peer' => $call->getOtherID(), 'message' => "**Shock Wave⚡️Radio**!
_A primeira rádio conservadora do Brasil!_
https://shockwaveradio.com.br

Que tal ajudar a **Shock Wave⚡️Radio** a crescer cada vez mais, melhorar a transmissão e adquirir equipamentos?
⚡️https://apoia.se/shockwaveradio", 'parse_mode' => 'Markdown']);
                } catch (\danog\MadelineProto\Exception $e) {
                    $logger->logger($e);
                } catch (\danog\MadelineProto\RPCErrorException $e) {
                    $logger->logger($e);
                } catch (\danog\MadelineProto\Exception $e) {
                    $logger->logger($e);
                }
                @unlink('/tmp/logs'.$call->getCallID()['id'].'.log');
                @unlink('/tmp/stats'.$call->getCallID()['id'].'.txt');
                $MadelineProto->getEventHandler()->cleanUpCall($call->getOtherID());

                return;
            }
        }
    }
    public function __toString(): string
    {
        return "VoIP status loop ".$this->call->getOtherId();
    }
}

class EventHandler extends \danog\MadelineProto\EventHandler
{
    const ADMINS = [24786050]; // @jvlianodorneles
    private $messageLoops = [];
    private $statusLoops = [];
    private $programmed_call;
    private $my_users;
    public $calls = [];

    // public function nowPlaying($returnvariable = null)
    // {
    //     // $url = 'https://icstream.rds.radio/status-json.xsl'; // Original
    //     $url = 'http://radioup.top:8010/radio.mp3';
    //     $jsonroba = file_get_contents($url);
    //     $jsonclear = json_decode($jsonroba, true);
    //     $metadata = explode('*', $jsonclear['icestats']['source'][16]['title']);

    //     if ($returnvariable == 'jsonclear') {
    //         return $jsonclear['icestats']['source'][16]['title'];
    //     }

    //     return $metadata;
    // }  // Este trecho serve para extrair alguns dados disponíveis no stream.

    public function configureCall($call)
    {
        $icsd = date('U');

        shell_exec('mkdir streams');

        file_put_contents('omg.sh', "#!/bin/bash \n mkfifo streams/$icsd.raw");

        file_put_contents('figo.sh', '#!/bin/bash'." \n".'ffmpeg -i http://radioup.top:8010/radio.mp3 -vn -f s16le -ac 1 -ar 48000 -acodec pcm_s16le pipe:1 > streams/'."$icsd.raw");

        shell_exec('chmod -R 0777 figo.sh omg.sh');

        shell_exec('./omg.sh');

        shell_exec("screen -S RDSstream$icsd -dm ./figo.sh");

        $call->configuration['enable_NS'] = false;
        $call->configuration['enable_AGC'] = false;
        $call->configuration['enable_AEC'] = false;
        $call->configuration['log_file_path'] = '/tmp/logs'.$call->getCallID()['id'].'.log'; // Default is /dev/null
        //$call->configuration["stats_dump_file_path"] = "/tmp/stats".$call->getCallID()['id'].".txt"; // Default is /dev/null
        $call->parseConfig();
        $call->playOnHold(["streams/$icsd.raw"]);
        if ($call->getCallState() === \danog\MadelineProto\VoIP::CALL_STATE_INCOMING) {
            if ($call->accept() === false) {
                $this->logger('DID NOT ACCEPT A CALL');
            }

            var_dump($call->getVisualization());

            //trying to get the encryption emojis 5 times...
            $b00l = 0;
            while ($b00l < 5) {
                try {
                    $this->messages->sendMessage(['peer' => $call->getOtherID(), 'message' => 'Emojis: '.implode('', $call->getVisualization())]);
                    $b00l = 5;
                } catch (\danog\MadelineProto\Exception $e) {
                    $this->logger($e);
                    $b00l++;
                }
            }
        }
        if ($call->getCallState() !== \danog\MadelineProto\VoIP::CALL_STATE_ENDED) {
            $this->calls[$call->getOtherID()] = $call;

            // try {
            //     $call->mId = yield $this->messages->sendMessage(['no_webpage' => true, 'peer' => $call->getOtherID(), 'message' => 'Você está ouvindo:<b>'.$this->nowPlaying()[1].'</b>  '.$this->nowPlaying()[2].'<br>Tipo: <i>'.$this->nowPlaying()[0].'</i>', 'parse_mode' => 'html'])['id'];
            //     $this->jsonmoseca = $this->nowPlaying('jsonclear');
            // } catch (\Throwable $e) {
            //     $this->logger($e);
            // }  // Este trecho serve para mandar uma mensagem mostrando os dados extraídos pelo trecho comentado anteriormente.

            $this->messageLoops[$call->getOtherID()] = new MessageLoop($this, $call);
            $this->statusLoops[$call->getOtherID()] = new StatusLoop($this, $call);
            $this->messageLoops[$call->getOtherID()]->start();
            $this->statusLoops[$call->getOtherID()]->start();
        }
        // yield $this->messages->sendMessage(['message' => var_export($call->configuration, true), 'peer' => $call->getOtherID()]);
    }

    public function cleanUpCall($user)
    {
        if (isset($this->calls[$user])) {
            unset($this->calls[$user]);
        }
        if (isset($this->messageLoops[$user])) {
            $this->messageLoops[$user]->signal(true);
            unset($this->messageLoops[$user]);
        }
        if (isset($this->statusLoops[$user])) {
            $this->statusLoops[$user]->signal(true);
            unset($this->statusLoops[$user]);
        }
    }
    public function makeCall($user)
    {
        try {
            if (isset($this->calls[$user])) {
                if ($this->calls[$user]->getCallState() === \danog\MadelineProto\VoIP::CALL_STATE_ENDED) {
                    yield $this->cleanUpCall($user);
                } else {
                    yield $this->messages->sendMessage(['peer' => $user, 'message' => "Eu já estou ligado em você!"]);
                    return;
                }
            }
            yield $this->configureCall(yield $this->requestCall($user));
        } catch (\danog\MadelineProto\RPCErrorException $e) {
            try {
                if ($e->rpc === 'USER_PRIVACY_RESTRICTED') {
                    $e = 'Verifique as configurações de Privacidade e Segurança para as Chamadas de voz para que eu consiga ligar para você!';
                }/* elseif (strpos($e->rpc, 'FLOOD_WAIT_') === 0) {
                    $t = str_replace('FLOOD_WAIT_', '', $e->rpc);
                    $this->programmed_call[] = [$user, time() + 1 + $t];
                    $e = "I'll call you back in $t seconds.\nYou can also call me right now.";
                }*/
                yield $this->messages->sendMessage(['peer' => $user, 'message' => (string) $e]);
            } catch (\danog\MadelineProto\RPCErrorException $e) {
            }
        } catch (\Throwable $e) {
            yield $this->messages->sendMessage(['peer' => $user, 'message' => (string) $e]);
        }
    }
    public function handleMessage($chat_id, $from_id, $message)
    {
        try {
            if (!isset($this->my_users[$from_id]) || $message === '/start') {
                $this->my_users[$from_id] = true;
                $message = '/meliga';
                yield $this->messages->sendMessage(['no_webpage' => true, 'peer' => $chat_id, 'message' => "**Shock Wave⚡️Radio**!

Ligue para mim para ouvir a **melhor programação** conservadora do Brasil! Ou você pode me enviar o comando `/meliga` para fazer com que _eu_ ligue para _você_ (não esqueça de ajustar as configurações de `Chamadas de voz` nas opções de `Privacidade e Segurança`!).

Você pode, inclusive, escapar daquela reunião xarope com o comando `/meavisa`:

`/meavisa 29 August 2020` - Ligue para mim no dia 29 de agosto de 2020
`/meavisa +1 hour 30 minutes` - Me ligue em uma hora e meia
`/meavisa next Thursday` - Me ligue na próxima quinta-feira à meia-noite

Envie o comando `/start` para ver essa mensagem novamente.

Ah, e eu sou um _userbot_ que faz uso da @MadelineProto, criado pelo @danogentili.

Código fonte: https://github.com/danog/MadelineProto

Arte criada pelo mestre
👑 **Mister Romanini, o Imperador Afrobege** 👑.", 'parse_mode' => 'Markdown']);
            }
            if (!isset($this->calls[$from_id]) && $message === '/meliga') {
                yield $this->makeCall($from_id);
            }
            if (\strpos($message, '/meavisa') === 0) {
                $time = \strtotime(\str_replace('/meavisa ', '', $message));
                if ($time === false) {
                    yield $this->messages->sendMessage(['peer' => $chat_id, 'message' => 'Formato inválido!']);
                } elseif ($time - \time() <= 0) {
                    yield $this->messages->sendMessage(['peer' => $chat_id, 'message' => 'Formato inválido']);
                } else {
                    yield $this->messages->sendMessage(['peer' => $chat_id, 'message' => 'Pode deixar que eu te ligo! 😎️']);
                    $this->programmed_call[] = [$from_id, $time];
                    $key = \count($this->programmed_call) - 1;
                    yield \danog\MadelineProto\Tools::sleep($time - \time());
                    yield $this->makeCall($from_id);
                    unset($this->programmed_call[$key]);
                }
            }
            if ($message === '/broadcast' && \in_array(self::ADMINS, $from_id)) {
                $time = \time() + 100;
                $message = \explode(' ', $message, 2);
                unset($message[0]);
                $message = \implode(' ', $message);
                $params = ['multiple' => true];
                foreach (yield $this->getDialogs() as $peer) {
                    $params []= ['peer' => $peer, 'message' => $message];
                }
                yield $this->messages->sendMessage($params);
            }
        } catch (\danog\MadelineProto\RPCErrorException $e) {
            try {
                if ($e->rpc === 'USER_PRIVACY_RESTRICTED') {
                    $e = 'Verifique as configurações de Privacidade e Segurança para as Chamadas de voz para que eu consiga ligar para você!';
                } /*elseif (strpos($e->rpc, 'FLOOD_WAIT_') === 0) {
                    $t = str_replace('FLOOD_WAIT_', '', $e->rpc);
                    $e = "Too many people used the /meliga function. I'll be able to call you in $t seconds.\nYou can also call me right now";
                }*/
                
                yield $this->messages->sendMessage(['peer' => $chat_id, 'message' => (string) $e]);
            } catch (\danog\MadelineProto\RPCErrorException $e) {
            }
            $this->logger($e);
        } catch (\danog\MadelineProto\Exception $e) {
            $this->logger($e);
        }
    }

    public function onUpdateNewMessage($update)
    {
        if ($update['message']['out'] || $update['message']['to_id']['_'] !== 'peerUser' || !isset($update['message']['from_id'])) {
            return;
        }
        $this->logger->logger($update);
        $chat_id = $from_id = yield $this->getInfo($update)['bot_api_id'];
        $message = $update['message']['message'] ?? '';
        yield $this->handleMessage($chat_id, $from_id, $message);
    }

    public function onUpdateNewEncryptedMessage($update)
    {
        return;
        $chat_id = yield $this->getInfo($update)['InputEncryptedChat'];
        $from_id = yield $this->getSecretChat($chat_id)['user_id'];
        $message = isset($update['message']['decrypted_message']['message']) ? $update['message']['decrypted_message']['message'] : '';
        yield $this->handleMessage($chat_id, $from_id, $message);
    }

    public function onUpdateEncryption($update)
    {
        return;

        try {
            if ($update['chat']['_'] !== 'encryptedChat') {
                return;
            }
            $chat_id = yield $this->getInfo($update)['InputEncryptedChat'];
            $from_id = yield $this->getSecretChat($chat_id)['user_id'];
            $message = '';
        } catch (\danog\MadelineProto\Exception $e) {
            return;
        }
        yield $this->handleMessage($chat_id, $from_id, $message);
    }

    public function onUpdatePhoneCall($update)
    {
        if (\is_object($update['phone_call']) && isset($update['phone_call']->madeline) && $update['phone_call']->getCallState() === \danog\MadelineProto\VoIP::CALL_STATE_INCOMING) {
            yield $this->configureCall($update['phone_call']);
        }
    }

    /*public function onAny($update)
    {
        $this->logger->logger($update);
    }*/

    public function __construct($API)
    {
        parent::__construct($API);
        $this->programmed_call = [];
        foreach ($this->programmed_call as $key => list($user, $time)) {
            continue;
            $sleepTime = $time <= \time() ? 0 : $time - \time();
            \danog\MadelineProto\Tools::callFork((function () use ($sleepTime, $key, $user) {
                yield \danog\MadelineProto\Tools::sleep($sleepTime);
                yield $this->makeCall($user);
                unset($this->programmed_call[$key]);
            })());
        }
    }
    public function __sleep()
    {
        return ['programmed_call', 'my_users'];
    }
}

if (!\class_exists('\\danog\\MadelineProto\\VoIPServerConfig')) {
    die('Install the libtgvoip extension: https://voip.madelineproto.xyz'.PHP_EOL);
}

\danog\MadelineProto\VoIPServerConfig::update(
    [
        'audio_init_bitrate'      => 100 * 1000,
        'audio_max_bitrate'       => 100 * 1000,
        'audio_min_bitrate'       => 10 * 1000,
        'audio_congestion_window' => 4 * 1024,
    ]
);
$MadelineProto = new \danog\MadelineProto\API('session.madeline', ['secret_chats' => ['accept_chats' => false], 'logger' => ['logger' => 3, 'logger_level' => 5, 'logger_param' => \getcwd().'/MadelineProto.log'], 'updates' => ['getdifference_interval' => 10], 'serialization' => ['serialization_interval' => 30, 'cleanup_before_serialization' => true], 'flood_timeout' => ['wait_if_lt' => 86400]]);
foreach (['calls', 'programmed_call', 'my_users'] as $key) {
    if (isset($MadelineProto->API->storage[$key])) {
        unset($MadelineProto->API->storage[$key]);
    }
}
$MadelineProto->async(true);
$MadelineProto->loop(function () use ($MadelineProto) {
    yield $MadelineProto->start();
    yield $MadelineProto->setEventHandler('\EventHandler');
});
$MadelineProto->loop();
