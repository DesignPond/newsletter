<?php namespace designpond\newsletter\Newsletter\Worker;

interface SubscriptionWorkerInterface{

    public function activate($email,$newsletter_id);
    public function subscribe($subscriber, array $newsletter_ids);
    public function make($email,$newsletter_id);
    public function exist($email);
    public function unsubscribe($subscriber, array $newsletter_ids);

}