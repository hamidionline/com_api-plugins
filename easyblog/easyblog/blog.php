<?php
/**
 * @package API plugins
 * @copyright Copyright (C) 2009 2014 Techjoomla, Tekdi Technologies Pvt. Ltd. All rights reserved.
 * @license GNU GPLv2 <http://www.gnu.org/licenses/old-licenses/gpl-2.0.html>
 * @link http://www.techjoomla.com
*/

defined('_JEXEC') or die( 'Restricted access' );

jimport('joomla.user.user');
jimport( 'simpleschema.category' );
jimport( 'simpleschema.person' );
jimport( 'simpleschema.blog.post' );

require_once( EBLOG_HELPERS . '/date.php' );
require_once( EBLOG_HELPERS . '/string.php' );
require_once( EBLOG_CLASSES . '/adsense.php' );

//for image upload
require_once( EBLOG_CLASSES . '/mediamanager.php' );
require_once( EBLOG_HELPERS . '/image.php' );
require_once( EBLOG_CLASSES . '/easysimpleimage.php' );
require_once( EBLOG_CLASSES . '/mediamanager/local.php' );
require_once( EBLOG_CLASSES . '/mediamanager/types/image.php' );

class EasyblogApiResourceBlog extends ApiResource
{

	public function __construct( &$ubject, $config = array()) {
		parent::__construct( $ubject, $config = array() );
	}
	public function delete()
	{
	$this->plugin->setResponse($this->delete_blog());
	}
	public function post()
	{    	
		$input = JFactory::getApplication()->input;
		$blog = EasyBlogHelper::getTable( 'Blog', 'Table' );
		$post = $input->post->getArray(array());
		$log_user = $this->plugin->get('user')->id;
		$res = new stdClass;
		
		//code for upload
		$blog->bind($post);

		$blog->permalink = str_replace('+','-',$blog->title);
		$blog->published = 1;
		
		//$blog->write_content = 1;
		//$blog->write_content_hidden = 1;
		
		$blog->created_by = $log_user;
		//this come from app side
		$blog->allowcomment = $post['allowcomment'];
		$blog->subscription = $post['subscription'];
		$blog->frontpage = 1;
		$blog->send_notification_emails = 1;
		//
		$blog->created = date("Y-m-d h:i:s");
		$blog->publish_up = date("Y-m-d h:i:s");
		$blog->created_by = $this->plugin->getUser()->id;

			if (!$blog->store()) {
				$this->plugin->setResponse( $this->getErrorResponse(404, $blog->getError()) );
				return;
			}
			
			$item = EasyBlogHelper::getHelper( 'SimpleSchema' )->mapPost($blog, '<p><br><pre><a><blockquote><strong><h2><h3><em><ul><ol><li>');
			

			$this->plugin->setResponse( $item );
   	   
	}
	
	public function get() {
		$input = JFactory::getApplication()->input;
		$model = EasyBlogHelper::getModel( 'Blog' );
		$config = EasyBlogHelper::getConfig();
		$id = $input->get('id', null, 'INT');

		// If we have an id try to fetch the user
		$blog = EasyBlogHelper::getTable( 'Blog' );
		$blog->load( $id );
		
		if (!$id) 
		{
			$this->plugin->setResponse( $this->getErrorResponse(404, 'Blog id cannot be blank') );
			return;
		}
				
		if (!$blog->id) 
		{
			$this->plugin->setResponse( $this->getErrorResponse(404, 'Blog not found') );
			return;
		}

		$item = EasyBlogHelper::getHelper( 'SimpleSchema' )->mapPost($blog, '<p><br><pre><a><blockquote><strong><h2><h3><em><ul><ol><li><iframe>');
		$item->isowner = ( $blog->created_by == $this->plugin->get('user')->id )?true:false;
		$item->allowcomment = $blog->allowcomment;
		$item->allowsubscribe = $blog->subscription;
		// Tags
		$modelPT	= EasyBlogHelper::getModel( 'PostTag' );
		$item->tags = $modelPT->getBlogTags($blog->id);
		
		//created by vishal - for show extra images
		//$item->text = preg_replace('/"images/i', '"'.JURI::root().'images', $item->text );
		$item->text = str_replace('href="','href="'.JURI::root(),$item->text);
		$item->text = str_replace('src="','src="'.JURI::root(),$item->text);
				
		$this->plugin->setResponse( $item );
	}
	public function delete_blog()
	{		
		$app = JFactory::getApplication();
		$id = $app->input->get('id',0,'INT');
		$blog = EasyBlogHelper::getTable( 'Blog', 'Table' );
		$blog->load( $id );
		if(!$blog->id || !$id)
		{
			$res->status =0;	
			$res->message='blog not exists';
			return $res;	
		}
		else
		{
			$val = $blog->delete($id);
			$re->status = $val;
			$res->message='blog deleted successfully';
			return $res;
		}	
	}	
}
