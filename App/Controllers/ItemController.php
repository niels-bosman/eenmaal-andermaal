<?php

namespace App\Controllers;

use App\Interfaces\IController;
use App\Core\Database;
use Error;

/**
 * Item Controller
 * All CRUD operations
 * 
 */
class ItemController implements IController
{
    /**
     * @var Database $database Database class which contains all generic CRUD functions.
     */
    private $database;

    /**
     * @var string $table Table name on which the CRUD operations should apply.
     */
    private static $table = 'Item';

    /**
     * ItemController constructor.
     */
    public function __construct()
    {
        $this->database = new Database;
    }

    /**
     * @param   array       $data   Associative array with all Item  data (Title, Description, City, CountryID, StartingPrice, StartDate, EndDate, PaymentMethod, PaymentInstructions, ShippingCosts, SendInstructions, SellerID).
     * @return  array|null          Returns created Item  as array or null.
     * @throws  Error               Throws error when Item  could not be created.
     */
    public function create(array $data): ?array
    {
        // Extract categories before adding item to database
        $cbic = new CategoriesByItemController;

        $categories = $data['Categories'];
        unset($data['Categories']);

        $id = $this->database->create(self::$table, $data);

        foreach ($categories as $category) {
            try {
                $values = array("ItemID" => $id, "CategoryID" => $category);
                $cbic->create($values);
            } catch (Error $err) {
                throw $err;
            }
        }

        if ($id) {
            return $this->get($id);
        }

        throw new Error('Item niet aangemaakt!');
    }

    /**
     * @param   int           $id   Get row where ID=$id
     * @return  array|null          Returns fetched row or null
     * @throws  Error               Throws error when no item is found.
     */
    public function get(int $id): ?array
    {
        $result = $this->database->get(self::$table, $id);

        if ($result) {
            return $result;
        }

        throw new Error("Item met id = $id niet gevonden!");
    }

    /**
     * @param int           $id     The ID to get detailed information from
     * @return array|null           Returns matching detailed item information
     * @throws Error                Throws error when item could not be found
     */
    public function getDetailed(int $id): ?array
    {
        $result = $this->database->customQuery("
            SELECT I.*, A.Username, B.ID as BiddingID, B.AccountID as BidderID, B.Time as BiddingTime, B.Amount as BiddingAmount
            FROM Item I
            LEFT JOIN Bidding B
            ON I.ID = B.ItemID
            LEFT JOIN Account A
            ON I.SellerID = A.ID
            WHERE I.ID = $id
            ORDER BY B.Amount DESC;
        ");

        if ($result) {
            return $result;
        }

        throw new Error("Item met id = $id niet gevonden!");
    }

    /**
     * Get's all the Items that belong to a specific account.
     * @param int $accountId
     * @return array|null
     */
    public function getByAccount(int $accountId): array
    {
        $result = $this->database->customQuery("
			SELECT *
			FROM Item I
			WHERE I.SellerID = $accountId
			AND I.StartDate < GETDATE() AND I.EndDate > GETDATE()
			ORDER BY I.EndDate
        ");

        return $result ?? [];
    }

    /**
     * @return array|null   Returns array with all iterms
     * @throws  Error               Throws error when no items were found.
     */
    public function index(): ?array
    {
        $result = $this->database->index(self::$table);

        if ($result) {
            return $result;
        }

        throw new Error("Geen Items gevonden!");
    }

    /**
     * @param   int         $id     Update item where ID=$id
     * @param   array       $data   Associative array of which the key is the column name to be updated with its value.
     * @return  array|null          The updated item as an associative array
     * @throws  Error               Throws error when item is not found or when updating failed.
     */
    public function update(int $id, array $data): ?array
    {
        if (!$this->get($id)) {
            return null;
        }

        $result = $this->database->update(self::$table, $id, $data);

        if ($result) {
            return $this->get($id);
        }

        throw new Error("Item waarvan ID = $id niet geupdate!");
    }

    /**
     * @param int           $id     Delete item with ID=$id
     * @return array|null           The deleted item as an associative array
     * @throws  Error               Throws error when item is not found or when updating failed.
     */
    public function delete(int $id): ?array
    {
        if (!$item = $this->get($id)) {
            return null;
        }

        $result = $this->database->delete(self::$table, $id);

        if ($result) {
            return $item;
        }

        throw new Error("Item waarvan ID = $id niet verwijderd!");
    }


    /**
     * Ensures that the auction is set to closed 
     * @param int           $id ID from Item
     * @param int           $buyerID ID from buyer
     * @param float         $sellingPrice The celling price
     * @return void
     * @throws Error  
     */
    public function handleSold(int $id, int $buyerID, float $sellingPrice): void
    {
        $item = $this->get($id);

        $result = $this->database->update(self::$table, $id, [
            'AuctionClosed' => !$item['AuctionClosed'],
            'SellingPrice' => $sellingPrice,
            'BuyerID' => $buyerID,
        ]);

        if (!$result) {
            throw new Error("Item status niet gewijzigd!");
        }
    }


    /**
     * @param int                   Update item view with 1 by ID=$id
     * @return array|null           The updated item as an associative array
     * @throws  Error               Throws error when item is not found or when updating failed.
     */
    public function increaseViews(int $id): ?array
    {
        if (!$item = $this->get($id)) {
            return null;
        }

        $result = $this->database->update(self::$table, $id, ['Views' => ++$item['Views']]);

        if ($result) {
            return $item;
        }

        throw new Error("Item waarvan ID = $id niet verwijderd!");
    }

    /**
     * @param   int                 Amount of items
     * @return  array|null          Returns fetched row or null
     * @throws  Error               Throws error when no item is found.
     */
    public function getFeaturedItems(int $amountItems): ?array
    {
        $result = $this->database->customQuery("SELECT Item.*, Temp1.Amount FROM (
            SELECT TOP $amountItems Item.ID, MAX(B.Amount) AS Amount FROM Item
            LEFT JOIN Bidding B on Item.ID = B.ItemID
            WHERE Item.AuctionClosed = 'false' AND Item.StartDate < GETDATE() AND Item.EndDate > GETDATE()
            GROUP BY Item.ID) AS Temp1
            LEFT JOIN Item ON Item.ID = Temp1.ID
            ORDER BY Item.Views  DESC 
            ");

        if ($result) {
            return $result;
        }

        throw new Error("Geen uitgelichte items gevonden...");
    }
	
	/**
	 * Function that is used to load up items in the auctions (veilingen) view. It accepts an array with filter data.
	 * @param int        $pageNumber Number of page
	 * @param int        $perPage    Number of items to display per page
	 * @param array|null $filters    Associative array which contains filters for: title, price and categoryId
	 * @return  array|null              Array containing all auctions found in the database
	 */
    // TODO: Add pagination
    public function getOverview(int $pageNumber, int $perPage, array $filters = null): ?array
    {
        $query = "SELECT I.ID, I.Title, I.EndDate, I.Thumbnail, MAX(IIF(B.Amount IS NULL, I.StartingPrice, B.Amount)) as HighestPrice
                  FROM Item I
                    LEFT JOIN Bidding B On I.ID = B.ItemID";
        // Add Join for category filter
        if ($filters && $filters['categoryId']) {
            $query .= " LEFT JOIN CategoriesByItem CBI On CBI.ItemID = I.ID";
        }

        $query .= " WHERE EndDate > CURRENT_TIMESTAMP
                    AND AuctionClosed = 'false'
                    AND Active = 1";

        // Apply filters
        if (isset($filters)) {
            foreach ($filters as $key => $value) {
                switch ($key) {
                    case 'searchValue':
                        $query .= " AND I.Title LIKE '%$value%' OR I.Description LIKE '%$value%'";
                        break;
                    case 'price':
                        $query .= " AND IIF(B.Amount IS NULL, I.StartingPrice, B.Amount) BETWEEN $value[0] AND $value[1]";
                        break;
                    case 'categoryId':
                        $query .= " AND CBI.CategoryID = $value";
                        break;
                }
            }
        }

        $query .= " GROUP BY I.ID, I.Title, I.Thumbnail, I.StartingPrice, I.EndDate
                    ORDER BY I.EndDate ASC";

        // Pagination
        $query .= " OFFSET (($pageNumber-1) * $perPage) ROWS FETCH NEXT $perPage ROWS ONLY";

        $result = $this->database->customQuery($query);

        if ($result) {
            return $result;
        }

        throw new Error("Geen veilingen gevonden!");
    }
	
	/**
	 * Function that is used to load up items in the auctions (veilingen) view.
	 * @param $pageNumber
	 * @param $perPage
	 * @return  array|null              Array containing all auctions found in the database
	 */
    public function getOverviewPagination($pageNumber, $perPage): ?array
    {
        $query = "SELECT * FROM Item ORDER BY ID DESC OFFSET (($pageNumber-1) * $perPage) ROWS FETCH NEXT $perPage ROWS ONLY";

        $result = $this->database->customQuery($query);

        if ($result) {
			return $result;
		}

        throw new Error("Geen veilingen gevonden!");
    }
	
	/**
	 * Function that is used to set an item Inactive
	 * @param int $id
	 * @return  void
	 */
    public function toggleInactive(int $id): void
    {
        $item = $this->get($id);

        $startDate = date("Y-m-d H:i:s");
        $endDate = date('Y-m-d H:i:s', strtotime($startDate . ' +' . $item['Duration'] . ' day'));

        if (!$item['Active']) {
            $result = $this->database->update(self::$table, $id, ['Active' => !$item['Active'], 'StartDate' => $startDate, 'EndDate' => $endDate]);
        } else {
            $result = $this->database->update(self::$table, $id, ['Active' => !$item['Active']]);
        }


        if (!$result) {
            throw new Error("Blokkeer status niet gewijzigd!");
        }
    }
}
