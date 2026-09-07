<?php

namespace App\Github;

enum GithubObjectKind: string
{
    case Blob = 'blob';
    case Tree = 'tree';
}
