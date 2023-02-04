<?php

// Emulate the behaviour of older versions of PHP to work around PHP8 backwards incompatible changes.
//
// As we do not have a test suite capable of exercising a large percentage of the code base, fixing
// issues as they are reported or ad-hoc discovered is not an efficent use of developer time.
//
// Provide string functions prefixed pf_ that emulate PHP7 behaviour the code base is written for.
//



