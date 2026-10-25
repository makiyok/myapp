<?php

namespace App\Enums;

/**
 * 支払い待ちの申し込みを取り消そうとした結果
 */
enum EntryCancelResult
{
    /**
     * 取り消して、押さえていた枠を戻した
     */
    case Cancelled;

    /**
     * Stripe ではすでに支払われていたので、取り消さずに支払い済みにした
     */
    case Paid;

    /**
     * Stripe の状態が分からず、お金の取りこぼしを避けるため取り消さなかった
     */
    case Failed;

    /**
     * もともと支払い待ちではなかったので、何もしなかった
     */
    case NotPending;
}
