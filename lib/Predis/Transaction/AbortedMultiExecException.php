<?php

namespace Predis\Transaction;

use Predis\PredisException;

class AbortedMultiExecException extends PredisException {
    // Aborted MULTI/EXEC transactions
}
