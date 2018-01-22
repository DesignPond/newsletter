<?php namespace designpond\newsletter\Newsletter\Repo;

use designpond\newsletter\Newsletter\Repo\NewsletterInterface;
use designpond\newsletter\Newsletter\Entities\Newsletter as M;

class NewsletterEloquent implements NewsletterInterface{

	protected $newsletter;

	public function __construct(M $newsletter)
	{
		$this->newsletter = $newsletter;
	}
	
	public function getAll(){
		
		return $this->newsletter->with(['campagnes','sent','draft','pending'])->get();
	}

	public function find($id){
				
		return $this->newsletter->find($id);
	}

    public function findMultiple($ids)
    {
        return $this->newsletter->whereIn('id',$ids)->get();
    }

	public function getSite($site_id)
	{
		return $this->newsletter->with(['campagnes'])->where('site_id', '=', $site_id)->get();
	}

	public function create(array $data){

		$newsletter = $this->newsletter->create(array(
			'titre'        => $data['titre'],
			'from_name'    => $data['from_name'],
            'from_email'   => $data['from_email'],
            'return_email' => $data['return_email'],
            'unsuscribe'   => $data['unsuscribe'],
            'preview'      => $data['preview'],
            'list_id'      => $data['list_id'],
            'color'        => (isset($data['color']) ? $data['color'] : ''),
            'logos'        => (isset($data['logos']) ? $data['logos'] : ''),
            'header'       => (isset($data['header']) ? $data['header'] : ''),
			'created_at'   => date('Y-m-d G:i:s'),
			'updated_at'   => date('Y-m-d G:i:s')
		));
		
		if( ! $newsletter )
		{
			return false;
		}
		
		return $newsletter;
		
	}
	
	public function update(array $data){

        $newsletter = $this->newsletter->findOrFail($data['id']);
		
		if( ! $newsletter )
		{
			return false;
		}

        $newsletter->fill($data);
		$newsletter->updated_at = date('Y-m-d G:i:s');

		$newsletter->save();
		
		return $newsletter;
	}

	public function delete($id){

        $newsletter = $this->newsletter->find($id);

		return $newsletter->delete();
		
	}
}
