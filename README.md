# ExceptionDumper

## Installation

    composer require germix/exception-dumper

## Description

Dump exceptions to file.

## Available formats

- text
- html
- json

## How to use

    try
    {
        // ...
    }
    catch(\Exception $ex)
    {
        $dumper = new ExceptionDumper('logs', 'log ~ ', 'html', true);
        $dumper->dump($ex);
    }

Its generate a file like this

    logs/log ~ 2020-01-04 12-30-02.html
