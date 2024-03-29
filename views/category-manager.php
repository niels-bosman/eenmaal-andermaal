<?php

use App\Controllers\CategoryController;
use App\Core\Router;

// Handle Pagination url params
$pageNumber = isset($_GET['pageNumber']) && is_numeric($_GET['pageNumber']) && $_GET['pageNumber'] > 0 ? (int) $_GET['pageNumber'] : 1;
$perPage = isset($_GET['perPage']) && is_numeric($_GET['perPage']) && $_GET['perPage'] <= 96 && $_GET['perPage'] > 0 ? (int) $_GET['perPage'] : 12;

$cc = new CategoryController();

// Get rubrieken
try {
  $categories = $cc->indexAll($pageNumber, $perPage);
} catch (Error $err) {
  $error = $err->getMessage();
}

$errors = [];

// Update rubriek
if (isset(($_POST['edit']))) {
  $data['id'] = htmlspecialchars($_POST['edit']);
  $data['name'] = htmlspecialchars($_POST['name']);

  if (strlen($data['name']) > 1 && is_string($data['name'])) {
    try {
      $cc->update($data['id'], ["Name" => $data['name']]);
      $categories = $cc->indexAll($pageNumber, $perPage);
    } catch (Error $err) {
      $errors['edit'] = $err->getMessage();
    }
  } else {
    $errors['edit'] = "Ongeldige input!";
  }

  Router::redirect($_SERVER['REQUEST_URI']);
}


// Remove rubriek
if (isset(($_POST['delete']))) {
  $data['id'] = (int) htmlspecialchars($_POST['delete']);

  if (is_integer($data['id']))
    try {
      $cc->delete($data['id']);
      $categories = $cc->indexAll($pageNumber, $perPage);
    } catch (Error $err) {
      $errors['delete'] = $err->getMessage();
    }
  else {
    $errors['delete'] = "Kon niet verwijderen!";
  }

  Router::redirect($_SERVER['REQUEST_URI']);
}

// Add rubriek
if (isset(($_POST['add']))) {
  $data['name'] = htmlspecialchars($_POST['addName']);
  $data['parentId'] = (int) htmlspecialchars($_POST['addParentId']);

  if (is_integer($data['parentId']) && is_string($data['name']) && strlen($data['name']) > 0)
    try {
      $cc->create(['ParentId' => $data['parentId'], 'Name' => $data['name']]);
      $categories = $cc->indexAll($pageNumber, $perPage);
    } catch (Error $err) {
      $errors['add'] = $err->getMessage();
    }
  else {
    $errors['add'] = "Kon niet toevoegen!";
  }

  Router::redirect($_SERVER['REQUEST_URI']);
}
?>

<main role="main" class="container">
  <div class="row py-5">
    <div class="col-12">
      <div class="row">
        <div class="col-2">
          <button type="button" class="btn btn-lg btn-success" data-toggle="modal" data-target="<?= "#addModal" ?>"><i class="far fa-plus"></i></button>

          <!-- Add Modal -->
          <div class="modal fade" id="addModal" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">
            <div class="modal-dialog">
              <div class="modal-content">
                <div class="modal-header">
                  <h5 class="modal-title" id="exampleModalLabel">Rubriek toevoegen</h5>
                  <button type="button" class="btn btn-close" data-dismiss="modal" aria-label="Close"><i class="far fa-times"></i></button>
                </div>
                <form action="<?= $_SERVER['REQUEST_URI'] ?>" method="post" class="mx-0">
                  <div class="modal-body d-flex flex-column">
                    <div class="mb-3">
                      <label for="name" class="form-label">Parent ID</label>
                      <input type="text" class="form-control" id="name" name="addParentId" value="<?= $_POST['addID'] ?? '' ?>">
                    </div>

                    <div class="mb-3">
                      <label for="name" class="form-label">Naam</label>
                      <input type="text" class="form-control" id="name" name="addName" value="<?= $_POST['addName'] ?? '' ?>">
                    </div>
                  </div>

                  <form action="<?= $_SERVER['REQUEST_URI'] ?>">
                    <div class="modal-footer d-flex justify-content-between">
                      <button type="button" class="btn btn-secondary" data-dismiss="modal">Annuleren</button>
                      <button type="submit" class="btn btn-success" name="add" value="1">Toevoegen</button>
                    </div>
                  </form>
                </form>
              </div>
            </div>
          </div>
        </div>

        <div class="col-10">
          <div class="alert alert-primary text-center text-uppercase">
            <h1 class="h3 m-0 font-weight-bold">Categorieën beheren</h1>
          </div>
        </div>

      </div>


      <?php foreach ($errors as $error) : ?>
        <div class="alert alert-danger" role="alert">
          <?= $error ?>
        </div>
      <?php endforeach; ?>

      <?php
      if (isset($errors)) {
        foreach ($errors as $error) {
          echo '        <div class="alert alert-danger">' . $error . '</div>';
        }
      }
      ?>

      <?php if (isset($categories) && count($categories) > 0) : ?>
        <table class="table">
          <thead>
            <tr>
              <th scope="col">ID</th>
              <th scope="col">Naam</th>
              <th scope="col">Parent</th>
              <th scope="col">Sorteernummer</th>
              <th scope="col">Acties</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($categories as $category) : ?>
              <tr>
                <th scope="row"><?= $category['ID'] ?></th>
                <td><?= $category['Name'] ?></td>
                <td><?= isset($category['ParentID']) ? '#' . $category['ParentID'] . ' ' . $cc->get($category['ParentID'])['Name'] : 'Geen' ?></td>
                <td><?= $category['SortNumber'] ?></td>
                <td>
                  <div class="btn-group btn-group-sm" role="group" aria-label="Basic example">
                    <button type="button" class="btn btn-warning" data-toggle="modal" data-target="<?= '#editModal' . $category['ID'] ?>"><i class="far fa-edit"></i></button>
                    <button type="button" class="btn btn-danger" data-toggle="modal" data-target="<?= "#deleteModal{$category['ID']}" ?>"><i class="far fa-trash-alt"></i></button>
                  </div>
                </td>

                <!-- Edit Modal -->
                <div class="modal fade" id="<?= 'editModal' . $category['ID'] ?>" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">
                  <div class="modal-dialog">
                    <div class="modal-content">
                      <div class="modal-header">
                        <h5 class="modal-title" id="exampleModalLabel">Rubriek #<?= $category['ID'] ?> wijzigen</h5>
                        <button type="button" class="btn btn-close" data-dismiss="modal" aria-label="Close"><i class="far fa-times"></i></button>
                      </div>
                      <form action="<?= $_SERVER['REQUEST_URI'] ?>" method="post" class="mx-0">
                        <div class="modal-body d-flex flex-column">
                          <div class="mb-3">
                            <label for="name" class="form-label">Naam</label>
                            <input type="text" class="form-control" id="name" name="name" value="<?= $category['Name'] ?>">
                          </div>
                        </div>

                        <form action="<?= $_SERVER['REQUEST_URI'] ?>">
                          <div class="modal-footer d-flex justify-content-between">
                            <button type="button" class="btn btn-secondary" data-dismiss="modal">Annuleren</button>
                            <button type="submit" class="btn btn-warning" name="edit" value="<?= $category['ID'] ?>">Wijzigen</button>
                          </div>
                        </form>
                      </form>
                    </div>
                  </div>
                </div>

                <!-- Delete Modal -->
                <div class="modal fade" id="<?= 'deleteModal' . $category['ID'] ?>" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">
                  <div class="modal-dialog">
                    <div class="modal-content">
                      <div class="modal-header">
                        <h5 class="modal-title" id="exampleModalLabel">Weet je zeker dat je deze veiling wil veranderen?</h5>
                        <button type="button" class="btn btn-close" data-dismiss="modal" aria-label="Close"><i class="far fa-times"></i></button>
                      </div>
                      <form action="<?= $_SERVER['REQUEST_URI'] ?>" method="post">
                        <div class="modal-footer d-flex justify-content-between">
                          <button type="submit" class="btn btn-danger" name="delete" value="<?= $category['ID'] ?>">Verwijderen</button>
                          <button type="button" class="btn btn-secondary" data-dismiss="modal">Annuleren</button>
                        </div>
                      </form>
                    </div>
                  </div>
                </div>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>

        <!-- Pagination -->
        <div class="custom-pagination">
          <form action="<?= $_SERVER['REQUEST_URI'] ?>" method="get">
            <!-- Page number -->
            <nav aria-label="Page navigation example">
              <ul class="pagination mb-0">
                <li class="page-item <?php if ($pageNumber === 1) echo 'disabled' ?>">
                  <button type="submit" name="pageNumber" class="page-link" tabindex="-1" value="<?= $pageNumber - 1 ?>" <?php if ($pageNumber === 1) echo 'disabled' ?>>Vorige</a>
                </li>
                <li class=" page-item disabled">
                  <a class="page-link" href="#"><?= $pageNumber ?></a>
                </li>
                <li class="page-item <?php if (!isset($auctions) || count($auctions) < $perPage) 'disabled' ?>">
                  <button type="submit" name="pageNumber" class="page-link" value="<?= $pageNumber + 1 ?>" <?php if (!isset($auctions) || count($auctions) < $perPage) 'disabled' ?>">Volgende</a>
                </li>
              </ul>
            </nav>

            <!-- Display per page -->
            <div class="input-group ml-3 perPage">
              <select name="perPage" class="form-control">
                <option value="12" <?php if ($perPage === 12) echo 'selected' ?>>12</option>
                <option value="24" <?php if ($perPage === 24) echo 'selected' ?>>24</option>
                <option value="48" <?php if ($perPage === 48) echo 'selected' ?>>48</option>
                <option value="96" <?php if ($perPage === 96) echo 'selected' ?>>96</option>
              </select>
              <button type="submit" class="btn btn-outline-primary"><i class="fas fa-sync"></i></button>
            </div>

          </form>
        </div>



      <?php else : ?>
        <div class="alert alert-danger">Geen veilingen gevonden</div>
      <?php endif; ?>
    </div>
</main>