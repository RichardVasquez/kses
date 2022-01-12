<?php

/*
 * THIS IS PRE-RELEASE CODE IN A DEVELOPMENT BRANCH
 *
 * Yes, it works, at times.  No, it's not set in stone.
 *
 * It'll get merged in an be the main version when it's
 * ready.
 */

namespace Kses;

const VERSION = '0.3.0';

const ERRORS = array(
    'PHP_VERSION'                => 'Kses requires PHP 5.6 or newer.',
    'ADD_PROTOCOLS_NO_ARG'       => 'kses::AddProtocols() did not receive an argument',
    'ADD_PROTOCOLS_BAD_ARG'      => 'kses::AddProtocols() did not receive a string or an array.',
    'ADD_PROTOCOL_NOT_STRING'    => 'kses::AddProtocol() requires a string.',
    'ADD_PROTOCOL_EMPTY'         => 'kses::AddProtocol() tried to add an empty/NULL protocol.',
    'REMOVE_PROTOCOL_NOT_STRING' => 'kses::RemoveProtocol() requires a string.',
    'REMOVE_PROTOCOL_EMPTY'      => 'kses::RemoveProtocol() tried to remove an empty/NULL protocol.',
    'REMOVE_PROTOCOLS_BAD_ARG'   => 'kses::RemoveProtocols() did not receive a string or an array.',
    'SET_PROTOCOLS_NO_ARG'       => 'kses::SetProtocols() did not receive an argument.',
    'SET_PROTOCOLS_BAD_ARG'      => 'kses::SetProtocols() did not receive a string or an array.',
    'ADD_HTML_NO_STRING'         => 'kses::AddHTML() requires the tag to be a string',
    'ADD_HTML_EMPTY'             => 'kses::AddHTML() tried to add an empty/NULL tag',
    'ADD_HTML_ATTRIBS_ARRAY'     => 'kses::AddHTML() requires an array (even an empty one) of attributes for ',
);

if(!defined('PHP_VERSION_ID'))
{
    die( ERRORS['PHP_VERSION']);
}

if(PHP_MAJOR_VERSION == 5 and PHP_MINOR_VERSION < 6)
{
    die( ERRORS['PHP_VERSION']);
}

/*
 * Removed kses::Protocols - was deprecated
 * removed kses::_hook - was deprecated
 */