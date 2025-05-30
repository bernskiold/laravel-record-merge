<?php

namespace Bernskiold\LaravelRecordMerge\Enums;

enum MergeStrategy: string
{

    case UseSource = 'source';

    case UseTarget = 'target';

    case Skip = 'skip';

}
