<?php

namespace SDF;

class TestRequest extends Request
{
    // Explicit public property to avoid creating dynamic properties in tests
    public string $value = '';
}
