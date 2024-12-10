<?php

// src/Controller/DefaultController.php
namespace App\Controller;

use App\Entity\Producto;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\Routing\Annotation\Route;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use App\Form\ProductoType;

class DefaultController extends AbstractController
{
    #[Route('/hello', name: 'principal')]
    public function hello(): Response
    {
        return $this->render('principal.html.twig');
    }

    #[Route('/tabla', name: 'tablass')]
    public function tabla(EntityManagerInterface $entityManager): Response
    {
        // Obtener todos los productos para mostrarlos
        $productos = $entityManager->getRepository(Producto::class)->findAll();

        // Renderizar la lista de productos
        return $this->render('lista.html.twig', [
            'productos' => $productos,
        ]);
    }

    #[Route('/registro', name: 'registro', methods: ['POST'])]
    public function registro(Request $request, EntityManagerInterface $entityManager): Response
    {
        // Obtener los datos del formulario
        $claveProducto = $request->request->get('clave');
        $nombre = $request->request->get('nombre');
        $precio = $request->request->get('precio');
    
        // Verificar si la clave ya existe
        $productoExistente = $entityManager->getRepository(Producto::class)->findOneBy(['clave' => $claveProducto]);
    
        if ($productoExistente) {
            // Si la clave ya existe, mostrar  error y ir de vuelta al formulario
            $this->addFlash('error', 'La clave del producto ya está en uso. Por favor, ingrese una clave diferente.');
            return $this->redirectToRoute('principal'); 
        }
    
        // Crear un nuevo objeto Producto
        $producto = new Producto();
        $producto->setClave($claveProducto);
        $producto->setNombre($nombre);
        $producto->setPrecio($precio);
    
        // Persistir el objeto en la base de datos
        $entityManager->persist($producto);
        $entityManager->flush(); // Guarda los cambios en la base de datos
    
        // Redirigir a la lista de productos después de registrar
        return $this->redirectToRoute('tablass');
    }

    #[Route('/exportar-excel', name: 'exportar_excel')]
    public function exportarExcel(EntityManagerInterface $entityManager): Response
    {
        // Obtener los productos de la base de datos
        $productos = $entityManager->getRepository(Producto::class)->findAll();

        // Crear un nuevo Spreadsheet
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        
        // Agregar encabezados
        $sheet->setCellValue('A1', 'Clave');
        $sheet->setCellValue('B1', 'Nombre');
        $sheet->setCellValue('C1', 'Precio');

        // Agregar los datos de los productos
        $row = 2;
        foreach ($productos as $producto) {
            $sheet->setCellValue('A' . $row, $producto->getClave());
            $sheet->setCellValue('B' . $row, $producto->getNombre());
            $sheet->setCellValue('C' . $row, $producto->getPrecio());
            $row++;
        }

        // Crear el archivo Excel
        $writer = new Xlsx($spreadsheet);
        $fileName = 'productos.xlsx';

        // Configurar la respuesta
        $response = new Response();
        $response->headers->set('Content-Type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        $response->headers->set('Content-Disposition', 'attachment;filename="' . $fileName . '"');
        $response->headers->set('Cache-Control', 'max-age=0');
        
        // Guardar el archivo temporalmente y devolverlo
        $temp_file = tempnam(sys_get_temp_dir(), 'php');
        $writer->save($temp_file);
        $response->setContent(file_get_contents($temp_file));

        // Eliminar el archivo temporal
        unlink($temp_file);

        return $response;
    }

    #[Route('/producto/eliminar/{id}', name: 'eliminar_producto', methods: ['POST', 'DELETE'])]
    public function eliminarProducto(int $id, EntityManagerInterface $entityManager): RedirectResponse
    {
        // Buscar el producto por su ID
        $producto = $entityManager->getRepository(Producto::class)->find($id);

        if ($producto) {
            // Eliminar el producto de la base de datos
            $entityManager->remove($producto);
            $entityManager->flush();

            // Mensaje de éxito
            $this->addFlash('success', 'Producto eliminado correctamente.');
        } else {
            // Mensaje de error si el producto no existe
            $this->addFlash('error', 'Producto no encontrado.');
        }

        // Redirigir a la lista de productos
        return $this->redirectToRoute('tablass');
    }
    #[Route('/buscar-producto', name: 'buscar_producto', methods: ['GET'])]
    public function buscarProducto(Request $request, EntityManagerInterface $entityManager): Response
   { 
    // Obtener el nombre del producto desde el formulario
    $nombreBuscado = $request->query->get('nombre');

    // Buscar productos que coincidan con el nombre (usando LIKE para búsqueda parcial)
    $productos = $entityManager->getRepository(Producto::class)->createQueryBuilder('p')
        ->where('p.nombre LIKE :nombre')
        ->setParameter('nombre', '%' . $nombreBuscado . '%')
        ->getQuery()
        ->getResult();

    // Renderizar la vista con los resultados de la búsqueda
    return $this->render('lista.html.twig', [
        'productos' => $productos,
    ]);
 }
 
    #[Route('/editar/{id}', name: 'editar_producto')]
    public function editarProducto(Request $request, EntityManagerInterface $em, $id): Response
    {
        // Buscar el producto por su ID
        $producto = $em->getRepository(Producto::class)->find($id);

        // Si el producto no existe, mostrar un error
        if (!$producto) {
            $this->addFlash('error', 'El producto no fue encontrado.');
            return $this->redirectToRoute('tablass');
        }

        // Crear el formulario y pasarlo al producto
        $form = $this->createForm(ProductoType::class, $producto);

        // Procesar la solicitud del formulario
        $form->handleRequest($request);

        // Verificar si el formulario fue enviado y es válido
        if ($form->isSubmitted() && $form->isValid()) {
            // Guardar los cambios en la base de datos
            $em->persist($producto);
            $em->flush();

            // Mensaje de éxito
            $this->addFlash('success', 'El producto ha sido actualizado exitosamente.');

            // Redirigir a la lista de productos
            return $this->redirectToRoute('tablass');
        }

        // Renderizar la vista del formulario de edición
        return $this->render('editar.html.twig', [
            'form' => $form->createView(),
            'producto' => $producto,
        ]);
    }
}
