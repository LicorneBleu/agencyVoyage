<?php
session_start();

if(!isset($_SESSION['user']) && empty($_SESSION['user'])){
 	header('Location: index.php');
}

$title = 'Modification séjour';
require 'inc/config.php';


$mimeTypesAllowed = [
	'image/png',
	'image/gif',
	'image/jpeg',
	'image/jpg',
	'image/pjpeg'
];

$maxSize = 5 * 1000 * 1000;

$dirUpload = 'img/upload/';




if(!empty($_POST)){

	$headers = [];
	$errors = [];
	$post = [];


	foreach ($_POST as $key => $value) {
		$post[$key] = trim(strip_tags($value));
	}


	// Vérification de l'Intitulé du séjour
	if(!empty($post['input_entitled'])){

		if(strlen($post['input_entitled']) < 2 || strlen($post['input_entitled']) > 100){
			$errors[] = 'L\'intitulé du séjour doit comporter entre 10 et 100 caractères';
		}
	} else {
		$errors[] = 'Vous devez entrer l\'intitulé du séjour';
	}


	// Vérification du pays sélectionné
	if ((!is_numeric($post['input_country'])) || ($post['input_country'] < 1 || $post['input_country'] > 241)) {
		$errors[] = 'Vous devez sélectionner le pays du séjour';
	}

	// Vérification de la durée sélectionné
	if(empty($post['input_term'])){
		$errors[] = 'Vous devez indiquer la durée du séjour';
	}

	// Vérification de l'hotel
	if(!empty($post['input_hostel'])){

		if(strlen($post['input_hostel']) < 2){
			$errors[] = 'Le nom de l\'hotel doit faire au moins 2 caractères';
		}
	} else {
		$errors[] = 'Vous devez sélectionner un hotel pour le séjour';
	}


	// Vérification du tarif du séjour
	if(!empty($post['input_price'])){
		$search = [' ', ','];
		$replace = ['', '.'];

		str_replace($search, $replace, $post['input_price']);

		// if(!is_numeric($post['input_tarif'])){
		// 	$errors [] = 'Le prix du séjour n\'est pas valide';
		// }
	} else {
		$errors [] = 'Veuilliez entrer le prix du séjour.';
	}


	// Vérification de la description du séjour
	if(!empty($post['input_description'])){

		if(strlen($post['input_description']) < 50 ){
			$errors[] = 'La description du séjour doit comporter au moins 50 caractères.';
		}
	} else {
		$errors[] = 'Vous devez entrer la description du séjour';
	}



	// Vérification de l'upload'
	if($_FILES['input_picture']['error'] == UPLOAD_ERR_OK){

		$finfo = new finfo(); // Instancie la class PHP FileInfo qui va permettre d'obtenir des informations plus précises sur le mime type du fichier
		$mimeType = $finfo->file($_FILES['input_picture']['tmp_name'], FILEINFO_MIME_TYPE); // Retourne quelque chose du style "image/jpg" ou "application/pdf"

		// Vérifie que le mime type du fichier uploadé corresponde a un mime type autorisé
		if(in_array($mimeType, $mimeTypesAllowed)){

			// Si le poid de l'image / du fichier est inférieur à la taille maxi autorisée
			if ($_FILES['input_picture']['size'] < $maxSize){

				$search = [' ', 'é', 'è', 'à', 'ù'];
				$replace = ['-', 'e', 'e', 'a', 'u'];

				// La concaténation du nom de fichier avec la fonction time() m'assure un nom de fichier unique et évite ainsi l'écrasement
				$newFileName = str_replace($search, $replace, $post['input_entitled'].'-'.$_FILES['input_picture']['name']);


				if(!is_dir($dirUpload)){
					if(!mkdir($dirUpload, 0777)){ // Fabrique un dossier avec tous les droits (chmod 777)
						$errors[] = 'Un problème est survenu lors de la création du répertoire d\'upload';
					}
				}

				$destination = $dirUpload.$newFileName;

				move_uploaded_file($_FILES['input_picture']['tmp_name'], $destination);

			} else {

				$errors[] = 'Votre fichier est trop lourd (2 Mo maxi)';
			}

		} else {
			$errors[] = 'Le format de fichier n\'est pas autorisé';
		}

	} //else {
	// 	$errors[] = 'Le téléchargement  n\'a pas fonctionné';
	// }




	if(count($errors) == 0){
		$formValid = true;

		$sql = 'UPDATE travels
				SET entitled 	= :new_entitled_param,
					hostel_name = :new_hostel_name_param,
					country_id 	= :new_country_id_param,
					description = :new_description_param,
					term 		= :new_term_param,
					price 		= :new_price_param,
					actived 	= :new_actived_param ';

		if(!empty($post['picture'])){
	      	$sql.= ', picture = :new_picture_param ';
	    }

		$sql.= ' WHERE id = :id_param ';

		$update = $bdd->prepare($sql);

		$update->bindValue(':new_entitled_param', $post['input_entitled']);
		$update->bindValue(':new_hostel_name_param', $post['input_hostel']);
		$update->bindValue(':new_country_id_param', $post['input_country']);
		$update->bindValue(':new_description_param', $post['input_description']);
		$update->bindValue(':new_term_param', $post['input_term']);
		$update->bindValue(':new_price_param', $post['input_price']);
		$update->bindValue(':new_actived_param', $post['input_actived']);
		$update->bindValue(':id_param', $_GET['id']);

		if(!empty($post['picture'])){
	      	$update->bindValue(':new_picture_param', $newFileName);
	    }

		$update->execute();
	} else {
		$formValid = false;
	}


}

$sql = 'SELECT * FROM countries ORDER BY nom_fr_fr';
$requete = $bdd->prepare($sql);
$requete->execute();
$countries = $requete->fetchAll(PDO::FETCH_ASSOC);

$sql2 = 'SELECT * FROM travels WHERE id = :param_id';
$requete = $bdd->prepare($sql2);
$requete->bindValue(':param_id', $_GET['id']);
$requete->execute();
$travel = $requete->fetch(PDO::FETCH_ASSOC);



?>
<?php include 'partials/header.php'; ?>

	<main class="pb-5">

		<h2 class="text-center mt-5">Modification de "<?=$travel['entitled'];?>"</h2>

		<!-- formulaire -->
		<div class="container">
	      	<div class="row pt-3">
	        	<div class="col-12 border border-info rounded p-5 small shadow">

	        		<?php if(isset($formValid) && $formValid == true): ?>
						<div class="col-12 p-3 mb-2 mx-auto alert alert-success">
							<strong><?=$post['input_entitled'];?></strong> a bien été mis à jour.
						</div>
					<?php elseif(isset($formValid) && $formValid == false): ?>
						<div class="col-12 p-3 mx-auto mb-2 mt-2 alert alert-danger">
							<?=implode('<br><hr>', $errors);?>
						</div>
					<?php endif;?>

	        		<form class="form-group" method="post" enctype="multipart/form-data">

	        			<div class="row mb-3">
	                		<div class="col-12">
								<label for="nom_sejour">Intitulé du séjour</label>
								<input type="text" name="input_entitled" id="nom_sejour" class="form-control" value="<?=$travel['entitled'];?>">
								<small class="text-muted">L'intitulé doit faire entre 10 et 100 caractères.</small>
	                		</div>
	                	</div>

	        			<div class="row mb-3">
	                		<div class="col-6">
								<label for="country">Pays du séjour</label>
								<select id="country" name="input_country" class="form-control">

									<option selected>veuillez sélectionner un pays</option>

									<?php foreach($countries as $country):?>
										<?php
											if($country['id'] === $travel['country_id']){
												$is_select = 'selected';
											}
											else {
												$is_select = '';
											}
										?>

									<option value="<?=$country['id']; ?>" <?php echo $is_select;?>><?=$country['nom_fr_fr']; ?></option>
									<?php endforeach;?>

								</select>
	                		</div>

	                		<div class="col-6">
								<label for="term">Durée du séjour</label>
								<select id="term" name="input_term" class="form-control">

									<?php
									// (condition) ? 'resultat si vraie' : 'else';
									?>

									<option selected>veulliez sélectionner une durée</option>
									<option value="2 jours" <?=($travel['term'] == '2 jours') ? 'selected' : '';?>>2 jours</option>
									<option value="1 semaine" <?=($travel['term'] == '1 semaine') ? 'selected' : '';?>>1 semaine</option>
									<option value="2 semaines" <?=($travel['term'] == '2 semaines') ? 'selected' : '';?>>2 semaines</option>
									<option value="3 semaines" <?=($travel['term'] == '3 semaines') ? 'selected' : '';?>>3 semaines</option>
									<option value="1 mois" <?=($travel['term'] == '1 mois') ? 'selected' : '';?>>1 mois</option>
								</select>
	                		</div>
	                	</div>

	        			<div class="row mb-3">
	                		<div class="col-6">
	                  			<label class="mb-3" for="hostel">Hotel</label>
								<input type="text" name="input_hostel" id="hostel" class="form-control" value="<?=$travel['hostel_name'];?>">
								<small class="text-muted">Le nom de l'hotel doit contenir au moins 2 caractères.</small>
	                		</div>
	                	</div>

	                	<div class="row mb-3">
	                		<div class="col-12">
								<label for="description">Description du séjour</label>
								<textarea class="form-control" rows="6" name="input_description" id="description" placeholder="Faites une déscription du séjour"><?=$travel['description'];?></textarea>
								<small class="text-muted">Le descriptif du séjour doit contenir au moins 100 caractères.</small>
	                		</div>
	                	</div>


	                	<div class="row mb-3">
	                		<div class="col-12 d-flex flex-column">
								<label for="picture">Selectionner une image</label>
								<input type="file" name="input_picture" id="picture" accept="image/*">
								<small class="text-muted mt-1">5 Mo pour la taille maximum de la photo. Formats acceptés : png, gif, jpeg, jpg, pjpeg</small>
							</div>
						</div>

						<div class="row mb-3">
	                		<div class="col-12">
	                			<h4 class="">Image correspondant au séjour</h4>
								<img class="img-fluid" src="http://localhost/php/agencevoyage/img/upload/<?=$travel['picture'];?>" alt="" >
							</div>
						</div>

						<div class="row mb-3">
	                		<div class="col-6">
								<label for="price">Tarif du séjour</label>
								<input type="text" name="input_price" id="price" class="form-control" value="<?=$travel['price'];?>">
								<small class="text-muted">Entrez le tarif sans espaces. Priviligiez le point à la virgule pour séparer les centimes. Ne pas ajouter la devise.</small>
							</div>
						</div>

						<div class="row mb-3">
	                		<div class="col-6">
				      			<input class="form-check-input" type="checkbox" name="input_actived" id="annonce_prio" <?=($travel['actived'] == 'off') ? '' : 'checked';?> >
				      			<label for="annonce_prio">Mettre cette annonce en avant</label>
				      		</div>
						</div>

						<div>
							<button type="submit" class="btn btn-info">Enregistrer l'annonce</button>
						</div>

					</form>

	            </div>
	        </div>
	    </div>
	</main>

<?php include 'partials/footer.php'; ?>
