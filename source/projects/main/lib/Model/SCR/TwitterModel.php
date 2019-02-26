<?php

/**
 * @package     Dipity
 * @copyright   Copyright (C) 2014 - 2017 Dipity B.V. All rights reserved.
 * @link        http://www.dipity.eu
 */

namespace Prototype\Model\SCR;

use Slim\Container;

class TwitterModel extends ScrModel
{
    public function __construct(Container $container)
    {
        parent::__construct($container);

        $this->getState()
            ->insert('hashtag')
            ->insert('username', 'CTAFlash')
            ->insert('limit', 20);
    }

    public function fetch($raw = false)
    {
        $result = new $this($this->container);
        $result->setData(['tweets' => []]);

        if ($this->container['settings']['customer_key'] && $this->container['settings']['customer_secret'] && $this->container['settings']['token'] && $this->container['settings']['token_secret']) {
            $oauthc = new \OAuth($this->container['settings']['customer_key'], $this->container['settings']['customer_secret'], OAUTH_SIG_METHOD_HMACSHA1, OAUTH_AUTH_TYPE_URI);
            $oauthc->enableDebug();
            $oauthc->setToken($this->container['settings']['token'], $this->container['settings']['token_secret']);

            try {
                $endpoint = 'https://api.twitter.com/1.1/';
                $url = $endpoint . 'statuses/user_timeline.json?screen_name=' . $this->getState()->username;

                if ($this->getState()->hashtag) {
                    $url = $endpoint . 'search/tweets.json?q=' . $this->getState()->hashtag . '&tweet_mode=extended';
                }

                $oauthc->fetch($url . '&count=' . $this->getState()->limit, null, OAUTH_HTTP_METHOD_GET, array("User-Agent" => "pecl/oauth"));
                $data = json_decode($oauthc->getLastResponse());

                if ($this->getState()->hashtag) {
                    $data = $data->statuses;
                }

                foreach ($data as $tweet) {
                    $this->sanitizeTweet($tweet);
                }

                $result->setData(['tweets' => $data]);

                return [$result];
            } catch (\OAuthException $e) {
            }
        }

        return [$result];
    }

    private function sanitizeTweet(&$tweet)
    {
        foreach ($tweet->entities->user_mentions as $mention) {
            $tweet->text = str_replace('@' . $mention->screen_name, '<a href="http://twitter.com/' . $mention->screen_name . '" target="_blank">@' . $mention->screen_name . '</a>', $tweet->text);
        }

        foreach ($tweet->entities->hashtags as $hashtag) {
            $tweet->text = str_replace('#' . $hashtag->text, '<a href="https://twitter.com/hashtag/' . $hashtag->text . '" target="_blank">#' . $hashtag->text . '</a>', $tweet->text);
        }

        foreach ($tweet->entities->urls as $url) {
            $tweet->text = str_replace($url->url, '<a href="' . $url->url . '" target="_blank">' . $url->url . '</a>', $tweet->text);
        }
    }
}
