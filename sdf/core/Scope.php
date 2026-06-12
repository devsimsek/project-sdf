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

    /**
     * All registered scope values.
     */
    private const ALL = [
        self::Controller,
        self::Helper,
        self::Global,
        self::System,
        self::View,
    ];

    /**
     * Validate that a scope string matches a registered context.
     *
     * @param string $scope The scope value to validate.
     * @return bool True if the scope is a known context.
     */
    public static function validate(string $scope): bool
    {
        return in_array($scope, self::ALL, true);
    }
}
