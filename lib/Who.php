<?php

/**
 * File: Who.php
 * Author: Raed Chammem (http://raed.tn/)
 * Last Modified: July 27th, 2015
 * @version 1.1
 *
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License as
 * published by the Free Software Foundation; either version 2 of
 * the License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details at:
 * http://www.gnu.org/copyleft/gpl.html
 *
 *
 * Typical Usage:
 *
 *   $who = new Who();
 *   if( $who->getTor()) {
 *    echo 'You are using Tor';
 *   }
 *
 * Tor Nodes Sampled from: http://torstatus.blutmagie.de/
 *
 * ISP Data Is Gathered from: http://whatismyipaddress.com/
 * 
 *
 */

class Who {

    private $ip;
    private $isp;
    private $country;
    private $tor;

    public function getIp() {
        return $this->ip;
    }

    public function getIsp() {
        return $this->isp;
    }

    public function getCountry() {
        return $this->country;
    }

    public function getTor() {
        return $this->tor;
    }

    public function getBrowser() {
        require './lib/Browser.php';
        $browser = new Browser();
        return $browser;
    }

    public function __construct() {
        $this->findUserIP();
        $this->findISP();
        $this->findTor();
    }

    private function findUserIP() {

        $client = @$_SERVER['HTTP_CLIENT_IP'];
        $forward = @$_SERVER['HTTP_X_FORWARDED_FOR'];
        $remote = $_SERVER['REMOTE_ADDR'];

        if (filter_var($client, FILTER_VALIDATE_IP)) {
            $ip = $client;
        } elseif (filter_var($forward, FILTER_VALIDATE_IP)) {
            $ip = $forward;
        } else {
            $ip = $remote;
        }

        $this->ip = $ip;
    }

    private function findISP() {
        $output = array();

        $curl = curl_init();
        curl_setopt($curl, CURLOPT_URL, 'http://whatismyipaddress.com/ip/' . $this->ip);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, TRUE);
        curl_setopt($curl, CURLOPT_USERAGENT, "Mozilla/5.0 (compatible; MSIE 8.0; Windows NT 6.0)");

        $data = explode("\n", curl_exec($curl));
        $sh = implode(" ", $data);

        preg_match_all('/<th>(.*?)<\/th><td>(.*?)<\/td>/s', $sh, $output, PREG_SET_ORDER);

        if (isset($output[4][2])) {
            $this->isp = strip_tags($output[4][2]);
        } else {
            $this->isp = NULL;
        }

        if (isset($output[11][2])) {
            $this->country = strip_tags($output[11][2]);
        } else {
            $this->country = NULL;
        }
    }

    private function findTor() {
        $this->tor = FALSE;

        $rawNodes = @file_get_contents('http://torstatus.blutmagie.de/ip_list_exit.php/Tor_ip_list_EXIT.csv');

        if (empty($rawNodes)) {
            $this->tor = NULL;
            die("Couldn't get exit nodes list");
        }

        $exitNodes = explode("\n", $rawNodes);

        foreach ($exitNodes as $nodes) {
            if ($this->ip == $nodes) {
                $this->tor = TRUE;
                break;
            }
        }
    }

}
