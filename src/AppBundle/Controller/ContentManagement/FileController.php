<?php

namespace AppBundle\Controller\ContentManagement;

use AppBundle\Controller\AppController;
use AppBundle;
use AppBundle\Entity\UploadedAsset;
use AppBundle\Repository\UploadedAssetRepository;
use Elasticsearch\Common\Exceptions\Conflict409Exception;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Doctrine\ORM\EntityManager;

class FileController extends AppController
{
	/**
	 * @Route("/data/file/upload", name="file.upload")
	 */
	public function uploadFileAction(Request $request)
	{

	}
	
	/**
	 * @Route("/data/file/init-upload/{sha1}/{size}" , name="file.init-upload")
     * @Method({"POST"})
	 */
	public function initUploadFileAction($sha1, $size, Request $request)
	{
		

		/** @var EntityManager $em */
		$em = $this->getDoctrine ()->getManager ();
		/** @var UploadedAssetRepository $repository */
		$repository = $em->getRepository ( 'AppBundle:UploadedAsset' );
		
		$user = $this->getUser()->getUsername();
		
		/**@var UploadedAsset $uploadedAsset*/
		$uploadedAsset = $repository->findOneBy([
			'sha1' => $sha1,
			'available' => false,
			'user' => $user,
			'available' => false,
		]);
		
		if(!$uploadedAsset) {
			$uploadedAsset = new UploadedAsset();
			$uploadedAsset->setSha1($sha1);
			$uploadedAsset->setUser($user);
			$uploadedAsset->setSize($size);
			$uploadedAsset->setUploaded(0);
				
		}
		
		$params = json_decode($request->getContent(), true);
		$uploadedAsset->setName('upload.bin');
		if(isset($params['name'])){
			$uploadedAsset->setName($params['name']);			
		}
		$uploadedAsset->setType('application/bin');
		if(isset($params['type'])){
			$uploadedAsset->setType($params['type']);			
		}
		$uploadedAsset->setAvailable(false);
		
		if($uploadedAsset->getSize() != $size){
			throw new Conflict409Exception("Target size mismatched ".$uploadedAsset->getSize().' '.$size);
		}
		
		//TODO check if the file can be found in the repository
		
		
		//Get temporyName
		$filename = $this->filename($sha1);
		

		if(file_exists($filename)) {
			$alreadyUploaded = filesize($filename);
			if($alreadyUploaded !== $uploadedAsset->getUploaded()){
				file_put_contents($filename, "");
				$uploadedAsset->setUploaded(0);
			}
			else{
				$uploadedAsset->setUploaded($alreadyUploaded);
			}
		}
		else {
			touch($filename);
			$uploadedAsset->setUploaded(0);
		}
		
		$em->persist($uploadedAsset);
		$em->flush($uploadedAsset);
		
		return new JsonResponse($uploadedAsset->getResponse());
	}
	
	private function filename($sha1) {
		$target = $this->getParameter('uploading_folder');
		if(!$target) {
			$target = sys_get_temp_dir();
		}
		
		return $target.$sha1;
		
	}
	
	/**
	 * @Route("/data/file/upload-chunk/{sha1}", name="file.uploadchunk")
	 */
	public function uploadChunkAction($sha1, Request $request)
	{
		/** @var EntityManager $em */
		$em = $this->getDoctrine ()->getManager ();
		/** @var UploadedAssetRepository $repository */
		$repository = $em->getRepository ( 'AppBundle:UploadedAsset' );
		
		$user = $this->getUser()->getUsername();
		
		/**@var UploadedAsset $uploadedAsset*/
		$uploadedAsset = $repository->findOneBy([
				'sha1' => $sha1,
				'available' => false,
				'user' => $user,
				'available' => false,
		]);
		
		if(!$uploadedAsset) {
			throw new NotFoundHttpException('Upload job not found');
		}
		
		
		$filename = $this->filename($sha1);
		if(!file_exists($filename)) {
			throw new NotFoundHttpException('tempory file not found');
		}		
		$content = $request->getContent();
		
		$myfile = fopen($filename, "a");
		$result = fwrite($myfile, $content);
		fflush($myfile);
		fclose($myfile);
		
		$uploadedAsset->setUploaded(filesize($filename));
		
		$em->persist($uploadedAsset);
		$em->flush($uploadedAsset);
		
		
		return new JsonResponse($uploadedAsset->getResponse());
	}
}