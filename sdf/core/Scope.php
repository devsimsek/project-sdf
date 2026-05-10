<?php
namespace SDF;

/**
 * Class Scope
 * Defines the execution contexts within the SDF framework.
 */
class Scope
{
    /** Controller logic context. */
    public const Controller = 'controller';
    
    /** View layer context. */
    public const Helper = 'helper';
    
    /** Global application context. */
    public const Global = 'global';
    
    /** Core system context. */
    public const System = 'system';
    
    /** View rendering context. */
    public const View = 'view';
}
