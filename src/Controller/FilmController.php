<?php

namespace App\Controller;

use App\Entity\Video;
use App\Repository\VideoRepository;
use DateTime;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Serializer\Encoder\JsonEncoder;
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;
use Symfony\Component\Serializer\Serializer;
use Symfony\Component\HttpFoundation\Request;

class FilmController extends AbstractController
{
    /**
     * @Route("/film", name="film")
     */
    public function index(): Response
    {
        return $this->render('film/index.html.twig', [
            'controller_name' => 'FilmController',
        ]);
    }

    /******************************************************************************************************************/

    /**
     * @Route("/getall", name="film_json")
     * @param VideoRepository $videoRepository
     * @param Request $request
     * @return Response
     */
    public function videoJson(VideoRepository $videoRepository, Request $request)
    {
        $filter = [];
        $em = $this->getDoctrine()->getManager();
        $metadata = $em->getClassMetadata(Video::class)->getFieldNames();
        foreach($metadata as $value){
            if ($request->query->get($value)){
                $filter[$value] = $request->query->get($value);
            }
        }
        return JsonResponse::fromJsonString($this->serializeJson($videoRepository->findBy($filter)));
    }

    /******************************************************************************************************************/

    /**
     * @Route("/get/{id}", name="json_communes", methods={"GET"})
     * @param VideoRepository $videoRepository
     * @param Request $request
     * @return JsonResponse
     */
    public function videoIdJson(VideoRepository $videoRepository, Request $request)
    {
        $id = $request->query->get('id');
        $idExist = $videoRepository->findOneBy(['id' => $id]);
        $response = new Response();

        if ($idExist) {
            if ($id === null) {
                $film = $videoRepository->findAll();
            } else {
                $film = $videoRepository->findBy(['id' => $id]);
            }

            return JsonResponse::fromJsonString($this->serializeJson($film));

        } else {
            return new JsonResponse('Cet id n\'existe pas', 400);
        }

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
     * @Route("/api/film/json/create", name="film_create", methods={"POST"})
     * @param Request $request
     * @param VideoRepository $videoRepository
     * @return JsonResponse
     */
    public function createFilm(Request $request, VideoRepository $videoRepository): JsonResponse
    {
        $film = new Video();
        $datas = json_decode($request->getContent(), true);
        $nom = $datas['nom'];
        $synopsis = $datas['synopsis'];
        $type = "film";
        $time = DateTime::createFromFormat('j/m/Y H:i:s', $datas['date']."00:00:00");
//        $time = $time->format('j/m/Y H:i:s');
        $token = $datas['token'];

        $nomExist = $videoRepository->findOneBy(['nom' => $nom]);

        if (!empty($token) && strlen($token) < 255 ) {
            if ($nomExist) {
                return new JsonResponse("Ce nom existe déjà", 400);
            } else {
                $film->setNom($nom);
                $film->setSynopsis($synopsis);
                $film->setType($type);
                $film->setDate($time);

                $entityManager = $this->getDoctrine()->getManager();
                $entityManager->persist($film);
                $entityManager->flush();
                return new JsonResponse('Film créé', 201);
            }
        }
    }

    /******************************************************************************************************************/

    /**
     * @Route("/api/film/json/update", name="film_update", methods={"PUT"})
     * @param Request $request
     * @param VideoRepository $videoRepository
     * @return Response
     */
    public function filmUpdate(Request $request, VideoRepository $videoRepository)
    {
        $entityManager = $this->getDoctrine()->getManager();
        $data = json_decode(
            $request->getContent(),
            true
        );
        $response = new Response();
        if (isset($data['id']) && isset($data['nom'])) {
            $id = $data['id'];
            $time = DateTime::createFromFormat('j/m/Y H:i:s', $data['date']."00:00:00");
            //        $time = $time->format('j/m/Y H:i:s');
            $film = $videoRepository->find($id);
            if ($film === null) {
                $response->setContent("Ce film n'existe pas");
                $response->setStatusCode(Response::HTTP_BAD_REQUEST);
            } else {
                $film->setNom($data['nom']);
                $film->setSynopsis($data['synopsis']);
                $film->setType($data['type']);
                $film->setDate($time);
                $entityManager->persist($film);
                $entityManager->flush();
                $response->setContent("Modification du film");
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
     * @Route("/api/film/json/delete", name="film_delete", methods={"DELETE"})
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
            $film = $videoRepository->find($data["id"]);
            if ($film === null) {
                $response->setContent("Ce film n'existe pas");
                $response->setStatusCode(Response::HTTP_BAD_REQUEST);
            } else {
                $entityManager->remove($film);
                $entityManager->flush();
                $response->setContent("Ce film à été delete");
                $response->setStatusCode(Response::HTTP_OK);
            }
        } else {
            $response->setContent("L'id n'est pas renseigné");
            $response->setStatusCode(Response::HTTP_BAD_REQUEST);
        }
        return $response;
    }

    /******************************************************************************************************************/

    /**
     * @Route("/token", name="gettoken", methods={"GET"})
     * @throws \Exception
     */
    public function getToken(): Response
    {
        $response = new Response();
        try {
            $token = random_bytes(255);
            $response->setStatusCode(Response::HTTP_OK);
            $response->setContent($token);
        } catch (\Exception $exception) {
            $response->setStatusCode(Response::HTTP_INTERNAL_SERVER_ERROR);
            $response->setContent($exception);
        }

        return $response;
    }
}
