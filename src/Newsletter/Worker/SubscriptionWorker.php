<?php namespace designpond\newsletter\Newsletter\Worker;

use designpond\newsletter\Newsletter\Repo\NewsletterInterface;
use designpond\newsletter\Newsletter\Repo\NewsletterUserInterface;
use designpond\newsletter\Newsletter\Worker\MailjetServiceInterface;
use designpond\newsletter\Newsletter\Worker\SubscriptionWorkerInterface;

class SubscriptionWorker implements SubscriptionWorkerInterface{
    
    protected $newsletter;
    protected $subscription;
    protected $mailjet;

    public function __construct(NewsletterInterface $newsletter, NewsletterUserInterface $subscription, MailjetServiceInterface $mailjet)
    {
        $this->newsletter = $newsletter;
        $this->subscription = $subscription;
        $this->mailjet = $mailjet;
    }

    /**
     * @param  \designpond\newsletter\Newsletter\Entities\Newsletter_users $subscriber
     * @param  array $newsletter_ids
     * @return void
     */
    public function subscribe($subscriber, array $newsletter_ids)
    {
        $newsletters = $this->newsletter->findMultiple($newsletter_ids);

        $subscriber->subscriptions()->attach($newsletter_ids);

        if(!$newsletters->isEmpty()){
            foreach ($newsletters as $newsletter){
                $this->mailjet->setList($newsletter->list_id);
                $this->mailjet->subscribeEmailToList($subscriber->email);
            }
        }

        return $subscriber;
    }

    public function activate($email,$newsletter_id)
    {
        $subscriber = $this->exist($email);

        // If not subscriber found make one
        if(!$subscriber || !$subscriber->activation_token){
            $subscriber = $this->make($email,$newsletter_id);
        }

        // If not activated return found subscriber to resend activation link
        if(!$subscriber->activated_at) {
            return $subscriber;
        }

        // If the subscriber is already subscribed
        if($subscriber->subscriptions->contains('id',$newsletter_id)) {
            return false;
        }

        return $subscriber;
    }

    public function make($email,$newsletter_id)
    {
        return $this->subscription->create([
            'email' => $email,
            'activation_token' => md5($email.\Carbon\Carbon::now()),
            'newsletter_id' => $newsletter_id
        ]);
    }

    public function exist($email)
    {
        return $this->subscription->findByEmail($email);
    }

    /**
     *
     *
     * @param  \designpond\newsletter\Newsletter\Entities\Newsletter_users $subscriber
     * @param  array $newsletter_ids
     * @throws \designpond\newsletter\Exceptions\DeleteUserException
     * @return void
     */
    public function unsubscribe($subscriber,array $newsletter_ids)
    {
        $subscriber->subscriptions()->detach($newsletter_ids);

        $newsletters = $this->newsletter->findMultiple($newsletter_ids);

        if(!$newsletters->isEmpty()){
            foreach ($newsletters as $newsletter){
                $this->mailjet->setList($newsletter->list_id);
                // Remove subscriber from list mailjet
                if(!$this->mailjet->removeContact($subscriber->email)) {
                    throw new \designpond\newsletter\Exceptions\DeleteUserException('Erreur avec la suppression de l\'abonnés sur mailjet');
                }
            }
        }

        $subscriber->load('subscriptions');

        if($subscriber->subscriptions->isEmpty()){
            $subscriber->delete();
            return null;
        }

        return $subscriber;
    }
}