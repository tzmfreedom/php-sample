<?php

namespace AppBundle\Controller;

use AppBundle\Entity\Foo;
use AppBundle\Entity\Todo;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

class TodoController extends Controller
{
    /**
     * @Route("/", methods={"GET"}, name="index_todo")
     */
    public function indexAction()
    {
        // $todos = $this->getDoctrine()->getRepository(Todo::class)->findAll();
        $todos = $this->getDoctrine()->getRepository(Todo::class)->findAllOrderedByTitle();
        return $this->render('todo/index.html.twig', array(
            'todos' => $todos,
            'hello' => 'world',
        ));
    }

    /**
     * @Route("/new", methods={"GET"}, name="new_todo")
     */
    public function newAction()
    {
        $todo = new Todo();
        $todo->setTitle("foo");
        $todo->setBody("bar");
        $form = $this->createTodoForm($todo);
        return $this->render('todo/new.html.twig', array(
            'form' => $form->createView(),
        ));
    }

    /**
     * @Route("/", methods={"POST"}, name="create_todo")
     */
    public function createAction(Request $request)
    {
        $form = $this->createTodoForm(new Todo());
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $todo = $form->getData();
            $manager = $this->getDoctrine()->getManager();
            $manager->persist($todo);
            $manager->flush();
            return $this->redirectToRoute('show_todo', ['id' => $todo->getId()]);
        }
        return $this->render('todo/new.html.twig', [
           'form' => $form->createView(),
        ]);
    }

    /**
     * @Route("/{id}", methods={"GET"}, name="show_todo", requirements={"id": "\d+"})
     */
    public function showAction($id)
    {
        $todo = $this->getDoctrine()->getRepository(Todo::class)->find($id);
        return $this->render('todo/show.html.twig', array(
            'id' => $id,
            'todo' => $todo,
        ));
    }

    /**
     * @Route("/{id}/edit", methods={"GET"}, name="edit_todo", requirements={"id": "\d+"})
     */
    public function editAction($id)
    {
        $todo = $this->getDoctrine()->getRepository(Todo::class)->find($id);
        $form = $this->createFormBuilder($todo)
            ->setAction($this->generateUrl("update_todo", ["id" => $id]))
            ->add('title', TextType::class)
            ->add('body', TextareaType::class)
            ->add('save', SubmitType::class, ['label' => 'タスクを更新する'])
            ->getForm();
        return $this->render('todo/edit.html.twig', array(
            'id' => $id,
            'form' => $form->createView(),
        ));
    }

    /**
     * @Route("/{id}", methods={"POST"}, name="update_todo")
     * @param Request $request
     * @param $id
     */
    public function updateAction(Request $request, $id)
    {
        $todo = $this->getDoctrine()->getRepository(Todo::class)->find($id);
        $form = $this->createFormBuilder($todo)
            ->setAction($this->generateUrl("update_todo", ["id" => $id]))
            ->add('title', TextType::class)
            ->add('body', TextareaType::class)
            ->add('save', SubmitType::class, ['label' => 'タスクを更新する'])
            ->getForm();
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $manager = $this->getDoctrine()->getManager();
            $manager->flush();
            return $this->redirect($this->generateUrl('show_todo', ["id" => $id]));
        }

        return $this->render('todo/edit.html.twig', array(
            'id' => $id,
            'form' => $form->createView(),
        ));
    }

    /**
     * @Route("/{id}/delete", methods={"GET"}, name="delete_todo")
     * @param $id
     */
    public function deleteAction($id)
    {
        $todo = $this->getDoctrine()->getRepository(Todo::class)->find($id);
        $manager = $this->getDoctrine()->getManager();
        $manager->remove($todo);
        $manager->flush();
        return $this->redirect($this->generateUrl('index_todo'));
    }

    /**
     * @Route("/foo/create")
     * @param Request $request
     * @return JsonResponse
     */
    public function fooAction(Request $request)
    {
        $entityManager = $this->getDoctrine()->getManager();
        $foo = new Foo();
        $foo->setTitle($request->query->get("title"));
        $foo->setBody($request->query->get("body"));
        $foo->setBar(true);
        $entityManager->persist($foo);
        $entityManager->flush();
        $entityManager->refresh($foo);
        return new JsonResponse([
            'id' => $foo->getId(),
            'title' => $foo->getTitle(),
            'body' => $foo->getBody(),
            'bar' => $foo->getBar(),
        ]);
    }

    private function createTodoForm(Todo $todo)
    {
        return $this->createFormBuilder($todo)
            ->setAction($this->generateUrl("create_todo"))
            ->add('title', TextType::class)
            ->add('body', TextareaType::class)
            ->add('save', SubmitType::class, ['label' => 'タスクを作成する'])
            ->getForm();
    }
}
