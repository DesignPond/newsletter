<?php namespace designpond\newsletter\Newsletter\Repo;

use designpond\newsletter\Newsletter\Entities\Newsletter_users as M;
use designpond\newsletter\Newsletter\Repo\NewsletterUserInterface;

class NewsletterUserEloquent implements NewsletterUserInterface{

	protected $user;

	public function __construct(M $user)
	{
		$this->user = $user;
        setlocale(LC_ALL, 'fr_FR.UTF-8');
	}
	
	public function getAll()
    {
		return $this->user->with(['subscriptions'])->get();
	}

    public function getAllNbr($nbr)
    {
        return $this->user->with(['subscriptions'])->take(5)->orderBy('id', 'desc')->get();
    }

	public function find($id)
    {
		return $this->user->with(['subscriptions'])->findOrFail($id);
	}

	public function findByEmail($email)
    {
        $user = $this->user->with(['subscriptions']);

        if(is_array($email))
        {
            return $user->whereIn('email', $email)->get();
        }
        else
        {
            $user = $user->where('email','=',$email)->get();

            return !$user->isEmpty() ? $user->first() : null;
        }
	}

    public function get_ajax($draw, $start, $length, $sortCol, $sortDir, $search){

        $columns = ['id','activated_at','activated_at','email','newsletter_id'];

        $iTotal  = $this->user->all()->count();

        if($search)
        {
            $order = isset($columns[$sortCol]) ? $columns[$sortCol] : 'last_name';

            $data = $this->user->where('email','LIKE','%'.$search.'%')
                                ->with(['subscriptions'])
                                ->orderBy($order, $sortDir)
                                ->take($length)
                                ->skip($start)
                                ->get();

            $recordsTotal = $data->count();
        }
        else
        {
            if($sortCol == 4)
            {
                $data = $this->user->with(['subscriptions' => function($query) use ($sortDir)
                {
                    $query->orderBy('newsletter_id', $sortDir);
                }])->take($length)->skip($start)->get();
            }
            else
            {
                $data = $this->user->with(['subscriptions'])
                                    ->orderBy($columns[$sortCol], $sortDir)
                                    ->take($length)
                                    ->skip($start)
                                    ->get();
            }

            $recordsTotal = $iTotal;
        }

        $output = array(
            "draw"            => $draw,
            "recordsTotal"    => $iTotal,
            "recordsFiltered" => $recordsTotal,
            "data"            => []
        );

        foreach($data as $abonne)
        {
            $row = [];

            $row['id']            = '<a class="btn btn-info btn-sm" href="'.url('build/subscriber/'.$abonne->id).'">&Eacute;diter</a>';
            $row['status']        = ($abonne->activated ? '<span class="label label-success">Confirmé</span>' : '<span class="label label-default">Email non confirmé</span>');
            $row['activated_at']  = ($abonne->activated ? $abonne->activated->formatLocalized('%d %B %Y') : '');
            $row['email']         = $abonne->email;
            $row['abo']           = '';

            if( !$abonne->subscriptions->isEmpty())
            {
                $abos       = $abonne->subscriptions->pluck('titre')->all();
                $row['abo'] = implode(',',$abos);
            }

            $row['delete']  = '<form action="'.url('build/subscriber/'.$abonne->id).'" method="POST">'.csrf_field().'<input type="hidden" name="_method" value="DELETE">';
            $row['delete'] .= '<input type="hidden" name="email" value="'.$abonne->email.'">';
            $row['delete'] .= '<button data-what="supprimer" data-action="Abonné '.$abonne->email.'" class="btn btn-danger btn-xs deleteNewsAction pull-right">Supprimer</button>';
            $row['delete'] .= '</form>';
            $output['data'][] = $row;
        }

        return json_encode( $output );

    }

	public function activate($token){

        $user = $this->user->where('activation_token','=',$token)->get()->first();

        if( ! $user )
        {
            return false;
        }

        $user->activated_at = date('Y-m-d G:i:s');
        $user->save();

        return $user;

    }

    public function subscribe($id,$newsletter_id)
    {
        $user = $this->user->find($id);

        if( ! $user )
        {
            return false;
        }

        $relation = $user->subscriptions()->pluck('newsletter_id');
        $contains = $relation->contains($newsletter_id);

        if(!$contains)
        {
            $user->subscriptions()->attach($newsletter_id);
        }
    }

	public function create(array $data){

		$user = $this->user->create([
            'email'            => $data['email'],
            'activation_token' => (isset($data['activation_token']) ? $data['activation_token'] : null),
            'activated_at'     => (isset($data['activated_at']) ? $data['activated_at'] : null),
            'created_at'       => date('Y-m-d G:i:s'),
            'updated_at'       => date('Y-m-d G:i:s')
        ]);
		
		if( ! $user )
		{
			return false;
		}

        if(isset($data['newsletter_id']))
        {
            // Sync the abos to newsletter we have
            $user->subscriptions()->attach($data['newsletter_id']);
        }

		return $user;
	}
	
	public function update(array $data){

        $user = $this->user->findOrFail($data['id']);
		
		if( ! $user )
		{
			return false;
		}

		if(isset($data['email'])){
            $user->email =  $data['email'];
        }

        $user->activation_token = (isset($data['activation_token']) ? $data['activation_token'] : null);
        $user->activated_at     = (isset($data['activated_at']) ? $data['activated_at'] : null);
		$user->updated_at = date('Y-m-d G:i:s');

		$user->save();

        if(isset($data['newsletter_id']))
        {
            // Sync the abos to newsletter we have
            $user->subscriptions()->sync($data['newsletter_id']);
        }
		
		return $user;
	}

    public function add(array $data){

        $user = $this->user->create(array(
            'email'            => $data['email'],
            'activated_at'     => date('Y-m-d G:i:s'),
            'created_at'       => date('Y-m-d G:i:s'),
            'updated_at'       => date('Y-m-d G:i:s')
        ));

        if( ! $user )
        {
            return false;
        }

        return $user;
    }

	public function delete($email){

		return $this->user->where('email', '=', $email)->delete();

	}

}
