<?php

namespace App\Controller;

use DateTimeImmutable;
use App\Entity\Product;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

class ProductController extends AbstractController
{
    public $em;

    public function __construct(EntityManagerInterface $em) {
        $this->em = $em;
    }
    
    #[Route('/product', name: 'createProduct', methods: ['POST'])]
    public function createProduct(Request $request){
        
        $product = new Product();

        $name = $request->get('name');
        if (is_null($name) || empty($name)) {
            return new JsonResponse('Name cannot be blank', Response::HTTP_BAD_REQUEST);
        }
        $product->setName($name);

        $description = $request->get('description');
        if (!is_null($description) && !empty($description)) {
            $product->setDescription($description);
        }

        $sku = $request->get('sku');
        if (is_null($sku) || empty($sku)) {
            return new JsonResponse('Sku cannot be blank', Response::HTTP_BAD_REQUEST);
        }
        
        $exists = $this->em->getRepository(Product::class)->findOneBy([
            'sku' => $sku
        ]);

        if ($exists) {
            return new JsonResponse('Sku already used!', Response::HTTP_BAD_REQUEST);
        }
        $product->setSku($sku);

        $price = $request->get('price');
        if ($price === null || !is_numeric($price)) {
            return new JsonResponse(['code' => 400, 'message' => 'price must be a valid float.'], Response::HTTP_BAD_REQUEST);
        }
        $product->setPrice((float) $price);

        $quantity = $request->get('quantity');
        if ($quantity === null || !is_numeric($quantity)) {
            return new JsonResponse(['code' => 400, 'message' => 'quantity must be a valid integer.'], Response::HTTP_BAD_REQUEST);
        }
        $product->setQuantity((int) $quantity);

        $weight = $request->get('weight');
        if ($weight === null || !is_numeric($weight)) {
            return new JsonResponse(['code' => 400, 'message' => 'weight must be a valid float.'], Response::HTTP_BAD_REQUEST);
        }
        $product->setWeight((float) $weight);

        $discount = $request->get('discount');
        if ($discount !== null && is_numeric($discount)) {
            $product->setDiscount((float) $discount);
        }

        $tagsJson = $request->get('tags');
        $tagsArray = json_decode($tagsJson, true);
        if ($tagsArray === null || !is_array($tagsArray)) {
            return new JsonResponse(['code' => 400, 'message' => 'tags must be a valid json.'], Response::HTTP_BAD_REQUEST);
        }
        $product->setTags($tagsArray);

        $attributesJson = $request->get('attributes');
        $attributesArray = json_decode($attributesJson, true);
        if ($attributesArray === null || !is_array($attributesArray)) {
            return new JsonResponse(['code' => 400, 'message' => 'attributes must be a valid json.'], Response::HTTP_BAD_REQUEST);
        }
        $product->setAttributes($attributesArray);

        if ($request->files->has('image')) {
            $file = $request->files->get('image');
            $fileName = md5(uniqid()) . '.' . $file->guessExtension();
            $file->move($this->getParameter('product.images.directory'), $fileName);
            $product->setImage($fileName);
        }else{
            return new JsonResponse(['code' => 400, 'message' => 'You need to upload an image for this product!'], Response::HTTP_BAD_REQUEST);
        }

        $product->setCreatedAt(new DateTimeImmutable());
        $product->setCreatedBy($this->getUser()->getEmail());
        $product->setUpdatedAt(new DateTimeImmutable());
        $product->setUpdatedBy($this->getUser()->getEmail());
        $product->setActive(true);

        $this->em->persist($product);
        $this->em->flush();
        
        return new JsonResponse(['code' => 200, 'message' => "Product with name : ".$request->get('name')." was created successfully!"], Response::HTTP_OK);
    }
}