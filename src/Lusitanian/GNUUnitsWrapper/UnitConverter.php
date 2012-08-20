<?php
/**
 * Base exception interface at library level.
 *
 * @category   GNUUnitsWrapper
 * @package    UnitConverter
 * @author     David Desberg <david@thedesbergs.com>
 * @copyright  Copyright (c) 2012 The authors
 * @license    http://www.opensource.org/licenses/mit-license.html  MIT License
 */
namespace Lusitanian\GNUUnitsWrapper;

/**
 * Unit converter class.
 */
class UnitConverter
{
    /**
     * Standard process name of GNU Units
     */
    const STANDARD_PROCESS_NAME = 'units';

    /**
     * The process name of the local GNU Units instance.
     * @var string
     */
    protected $processName;

    /**
     * Constructs an instance of UnitConverter.
     * @param string $processName
     */
    public function __construct($processName = self::STANDARD_PROCESS_NAME)
    {
        $this->processName = $processName;
    }

    /**
     * Variadic function. Accepts either two or three parameters.
     * If passed two parameters, expects param 1 to be a source string (e.g. 2 gigabytes) and param 2 to be a target unit.
     * If passed three parameters, expects param 1 to be a source quantity, param 2 to be a source unit, and param 3 to be a target unit.
     *
     * @return float
     * @throws Exception\InvalidArgumentException
     */
    public function convert()
    {

        $args = func_get_args();
        $argCount = count($args);

        // escape apostrophes since the string will be encapsulated
        array_walk( $args, function(&$val) { $val = str_replace( "'", "\\'", $val ); } );

        if( 3 === $argCount ) {
            // $sourceQuantity, $sourceUnit, $targetUnit
            return $this->processOutput( $this->run( "'{$args[0]} {$args[1]}' '{$args[2]}'" ) );
        } elseif( 2 === $argCount ) {
            // $sourceCombined, $targetUnit
            return $this->processOutput( $this->run( "'{$args[0]}' '{$args[1]}'" ) );
        }

        throw new Exception\InvalidArgumentException("Invalid number of arguments passed to UnitConverter::convert (expected 2 or 3, got $argCount)");
    }

    /**
     * @param $output
     * @return float
     * @throws Exception\UnknownUnitException
     * @throws Exception\UnableToOpenUnitsFileException
     * @throws Exception\ConformabilityException
     * @throws Exception\UnknownException
     */
    protected function processOutput($output)
    {
        $outputCount = count($output);

        if( 0 === $outputCount ) {
            throw new Exception\UnknownException('Received no output from units command.');
        }

        // we execute in terse mode, so 1 line _likely_ means success
        if( 1 === $outputCount ) {
            $output = $output[0];
            if( false !== stripos($output, 'Unknown unit') ) {
                // so much for success (:
                throw new Exception\UnknownUnitException( $output );
            }

            if( false !== stripos($output, 'unable to open units file') ) {
                // again, no success ):
                throw new Exception\UnableToOpenUnitsFileException( $output );
            }


            return filter_var( $output, FILTER_VALIDATE_FLOAT ); // should always be numeric
        }

        if( false !== stripos($output[0], 'conformability') ) {
            throw new Exception\ConformabilityException();
        }

        foreach($output as $line)
        {
            if( false !== stripos($line, 'error') ) {
                throw new Exception\UnknownException($line);
            }
        }

        throw new Exception\UnknownException('Unknown error'); // we will never have multiple lines and success (:

    }

    /**
     * Runs the unit converter program provided the given input and returns the output in an array.
     *
     * @param $input
     * @return array
     */
    protected function run($input)
    {
        exec( "{$this->processName} -t $input", $output );
        return $output;
    }
}
