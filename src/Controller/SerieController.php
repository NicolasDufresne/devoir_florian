<?php

namespace App\Controller;

use App\Entity\Video;
use App\Repository\VideoRepository;
use DateTime;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Serializer\Encoder\JsonEncoder;
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;
use Symfony\Component\Serializer\Serializer;

class SerieController extends AbstractController
{
    /**
     * @Route("/serie", name="serie")
     */
    public function index(): Response
    {
        return $this->render('serie/index.html.twig', [
            'controller_name' => 'SerieController',
        ]);
    }


    /******************************************************************************************************************/

    protected function serializeJson($objet): string
    {
        $encoder = new JsonEncoder();
        $defaultContext = [
            AbstractNormalizer::CIRCULAR_REFERENCE_HANDLER => function ($object, $format, $context) {
                return $object->getNom();
            },
        ];
        $normalizer = new ObjectNormalizer(null, null, null, null, null, null, $defaultContext);
        $serializer = new Serializer([$normalizer], [$encoder]);
        $jsonContent = $serializer->serialize($objet, 'json');
        return $jsonContent;
    }

    /******************************************************************************************************************/


    /**
     * @Route("/api/serie/json/create", name="serie_create", methods={"POST"})
     * @param Request $request
     * @param VideoRepository $videoRepository
     * @return JsonResponse
     */
    public function createserie(Request $request, VideoRepository $videoRepository): JsonResponse
    {
        $serie = new Video();
        $datas = json_decode($request->getContent(), true);
        $nom = $datas['nom'];
        $synopsis = $datas['synopsis'];
        $type = "serie";
        $time = DateTime::createFromFormat('j/m/Y H:i:s', $datas['date']."00:00:00");
        //        $time = $time->format('j/m/Y H:i:s');
        $token = $datas['token'];

        $nomExist = $videoRepository->findOneBy(['nom' => $nom]);

        if (!empty($token) && strlen($token) < 255 ) {
            if ($nomExist) {
                return new JsonResponse("Ce nom existe déjà", 400);
            } else {
                $serie->setNom($nom);
                $serie->setSynopsis($synopsis);
                $serie->setType($type);
                $serie->setDate($time);

                $entityManager = $this->getDoctrine()->getManager();
                $entityManager->persist($serie);
                $entityManager->flush();
                return new JsonResponse('serie créé', Response::HTTP_OK);
            }
        }
    }

    /******************************************************************************************************************/

    /**
     * @Route("/api/serie/json/update", name="serie_update", methods={"PUT"})
     * @param Request $request
     * @param VideoRepository $videoRepository
     * @return Response
     */
    public function serieUpdate(Request $request, VideoRepository $videoRepository)
    {
        $entityManager = $this->getDoctrine()->getManager();
        $data = json_decode(
            $request->getContent(),
            true
        );
        $response = new Response();
        if (isset($data['id']) && isset($data['nom'])) {
            $id = $data['id'];
            $date = DateTime::createFromFormat('j/m/Y H:i:s', $data['date']."00:00:00");
            //        $time = $time->format('j/m/Y H:i:s');
            $serie = $videoRepository->find($id);
            if ($serie === null) {
                $response->setContent("Cette serie n'existe pas");
                $response->setStatusCode(Response::HTTP_BAD_REQUEST);
            } else {
                $serie->setNom($data['nom']);
                $serie->setSynopsis($data['synopsis']);
                $serie->setType($data['type']);
                $serie->setDate($date);
                $entityManager->persist($serie);
                $entityManager->flush();
                $response->setContent("Modification de la serie");
                $response->setStatusCode(Response::HTTP_OK);
            }
        } else {
            $response->setContent("Erreur Bad Request");
            $response->setStatusCode(Response::HTTP_BAD_REQUEST);
        }
        return $response;
    }


    /******************************************************************************************************************/

    /**
     * @Route("/api/serie/json/delete", name="serie_delete", methods={"DELETE"})
     * @param Request $request
     * @param VideoRepository $videoRepository
     * @return Response
     */
    public function departementDelete(Request $request, VideoRepository $videoRepository)
    {
        $entityManager = $this->getDoctrine()->getManager();
        $response = new Response();
        $data = json_decode(
            $request->getContent(),
            true
        );
        if (isset($data["id"])) {
            $serie = $videoRepository->find($data["id"]);
            if ($serie === null) {
                $response->setContent("Cette serie n'existe pas");
                $response->setStatusCode(Response::HTTP_BAD_REQUEST);
            } else {
                $entityManager->remove($serie);
                $entityManager->flush();
                $response->setContent("Cette serie à été delete");
                $response->setStatusCode(Response::HTTP_OK);
            }
        } else {
            $response->setContent("L'id n'est pas renseigné");
            $response->setStatusCode(Response::HTTP_BAD_REQUEST);
        }
        return $response;
    }
}
