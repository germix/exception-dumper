<?php

namespace Germix\ExceptionDumper;

/**
 * @author Germán Martínez
 */
class ExceptionDumper
{
    private $file;
    private $path;
    private $prefix;
    private $format;
    private $withDateTime;

    public function __construct($path, $prefix, $format, $withDateTime)
    {
        $this->path = $path;
        $this->prefix = $prefix;
        $this->format = $format;
        $this->withDateTime = $withDateTime;
    }

    public function dump(\Throwable $ex)
    {
        $filename = $this->path . $this->prefix;
        if($this->withDateTime)
        {
            $filename .= (new \DateTime())->format('Y-m-d H-i-s');
        }

        if($this->format == 'text')
            $filename .= '.txt';
        else if($this->format == 'json')
            $filename .= '.json';
        else if($this->format == 'html')
            $filename .= '.html';
        else
            return;

        $this->file = fopen($filename, 'w');
        if($this->file != null)
        {
            if($this->format == 'json')
            {
                $this->dumpAsJson($ex);
            }
            else if($this->format == 'text')
            {
                $this->dumpAsText($ex, 0);
            }
            else if($this->format == 'html')
            {
                $this->dumpAsHtml($ex, 0);
            }
            fclose($this->file);
        }
    }

    private function dumpAsJson(\Throwable $ex)
    {
        $s = json_encode(
            [
                'message' => $ex->getMessage(),
                'code' => $ex->getCode(),
                'file' => $ex->getFile(),
                'line' => $ex->getLine(),
                'trace' => $ex->getTrace()
            ]
        , JSON_PRETTY_PRINT);
        fwrite($this->file, $s);
    }

    private function dumpAsText(\Throwable $ex, $level)
    {
        fwrite($this->file, $this->dumpLevel($level) . 'Message: ' . $ex->getMessage() . PHP_EOL);
        fwrite($this->file, $this->dumpLevel($level) . 'Code: ' . $ex->getCode() . PHP_EOL);
        fwrite($this->file, $this->dumpLevel($level) . 'File: ' . $ex->getFile() . PHP_EOL);
        fwrite($this->file, $this->dumpLevel($level) . 'Line: ' . $ex->getLine() . PHP_EOL);

        {
            fwrite($this->file, $this->dumpLevel($level) . 'Trace: ' . PHP_EOL);

            $i = 0;
            foreach($ex->getTrace() as $trace)
            {
                fwrite($this->file, $this->dumpLevel($level) . '       ');

                fwrite($this->file, '#' . $i. ' ' . $trace['file'] . '(' . $trace['line'] . ')' . PHP_EOL);
                fwrite($this->file, $this->dumpLevel($level) . '       ' . '    ');

                fwrite($this->file, $this->dumpFunction($trace));

                $i++;
                fwrite($this->file, PHP_EOL . PHP_EOL);
            }
        }
        if($ex->getPrevious())
        {
            fwrite($this->file, 'Previous:' . PHP_EOL);
            $this->dumpAsText($ex->getPrevious(), null,  $level + 1);
        }
    }

    private function dumpAsHtml($ex)
    {
        $s = '<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="robots" content="noindex,nofollow">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>' . $ex->getMessage() . '</title>
    <style>
        *
        {
            margin: 0;
            padding: 0;
            font-size: 12px;
            font-family: Courier New,Courier,Lucida Sans Typewriter,Lucida Typewriter,monospace;
            box-sizing: border-box;
        }
        body
        {
            background: aliceblue;
        }
        .title
        {
            color: white;
            background: #2d8ee3;
            box-shadow: 0 4px 10px 0 rgba(0, 0, 0, 0.2), 0 6px 20px 0 rgba(0, 0, 0, 0.3);
            margin-bottom: 28px;
            display: flex;
            align-items: center;
            padding: 14px;
            font-size: 24px;
            font-weight: bold;
        }
        .row
        {
            display: flex;
            margin: 12px;
            padding: 8px;
            background: white;
            box-shadow: 0 4px 8px 0 rgba(0, 0, 0, 0.2), 0 6px 20px 0 rgba(0, 0, 0, 0.02);
        }
        .label
        {
            width: 80px;
        }
        .message
        {

        }
        .traces-block
        {

        }
        .trace
        {
            margin-bottom: 12px;
        }
        .trace-file
        {
            font-weight: bolder;
        }
        .trace-function
        {
            margin-left: 32px;
        }
    </style>
</head>
<body>
    <div class="title">
        Exceptions
    </div>';

$s .= '
    <div class="row">
        <div class="label">
            <b>Message: </b>
        </div>
        <div class="message">' . $ex->getMessage() . '</div>
    </div>
    <div class="row">
        <div class="label">
            <b>Code: </b>
        </div>
        <div class="message">' . $ex->getCode() . '</div>
    </div>
    <div class="row">
        <div class="label">
            <b>File: </b>
        </div>
        <div class="message">' . $ex->getFile() . '</div>
    </div>
    <div class="row">
        <div class="label">
            <b>Line: </b>
        </div>
        <div class="message">' . $ex->getLine() . '</div>
    </div>
    <div class="row">
        <div class="label">
            <b>Class: </b>
        </div>
        <div class="message">' . get_class($ex) . '</div>
    </div>';

$s .= '
    <div class="row">
        <div class="label">
            <b>Trace: </b>
        </div>

        <div class="traces-block">';
            $i = 0;
            foreach($ex->getTrace() as $trace)
            {
            $s .= '
            <div class="trace">
                <div class="trace-file">
                    ' . '#' . $i . ' ' . $trace['file'] . '(' . $trace['line'] . ')';
                    $i++;
            $s .= '
                </div>';
            $s .= '
                <div class="trace-function">
                    ' . $this->dumpFunction($trace) . '
                </div>';
            $s .= '
            </div>';
            }
        $s .= '
        </div>';
    $s .= '
    </div>';
$s .= '
</body>
</html>';

        fwrite($this->file, $s);
    }

    /**
     * @param integer $level
     */
    private function dumpLevel($level)
    {
        while($level > 0)
        {
            $level--;
            fwrite($this->file, '  ', 1);
        }
    }

    private function dumpFunction($trace)
    {
        $s = '';
        
        if(array_key_exists('class', $trace))
        {
            $s = $trace['class'] . $trace['type'] . $trace['function'];
        }
        else if(array_key_exists('function', $trace))
        {
            $s = $trace['function'];
        }

        if(array_key_exists('args', $trace))
        {
            $s .= '(';
            $first = true;
            foreach($trace['args'] as $arg)
            {
                if (!$first)
                    $s .= ', ';
                $first = false;
                if($arg == null)            { $s .= 'NULL'; }
                else if (is_array($arg))    { $s .= 'Array'; }
                else if (is_object($arg))   { $s .= 'Object(' . get_class($arg) . ')'; }
                else                        { $s .= "'$arg'"; }
            }
            $s .= ')';
        }

        return $s;
    }
}
