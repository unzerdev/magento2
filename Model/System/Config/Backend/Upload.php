<?php
declare(strict_types=1);

namespace Unzer\PAPI\Model\System\Config\Backend;

use Exception;
use Magento\Config\Model\Config\Backend\File;
use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\Exception\FileSystemException;
use Magento\Framework\Exception\LocalizedException;

/**
 * @link  https://docs.unzer.com/
 */
class Upload extends File
{
    /**
     * Retrieve upload directory path
     *
     * @param string $uploadDir
     * @return string
     * @throws FileSystemException
     */
    protected function getUploadDirPath($uploadDir): string
    {
        $this->_mediaDirectory = $this->_filesystem->getDirectoryWrite(DirectoryList::CONFIG);
        return $this->_mediaDirectory->getAbsolutePath($uploadDir);
    }

    /**
     * Getter for allowed extensions of uploaded files
     *
     * @return array
     */
    protected function _getAllowedExtensions(): array
    {
        return ['pem', 'key'];
    }

    /**
     * Save uploaded file before saving config value
     *
     * @return $this
     * @throws LocalizedException
     */
    public function beforeSave(): Upload
    {
        $value = $this->getValue();
        $file = $this->getFileData();

        if (!empty($file)) {
            $uploadDir = $this->_getUploadDir();
            try {
                $uploader = $this->_uploaderFactory->create(['fileId' => $file]);
                $uploader->setAllowedExtensions($this->_getAllowedExtensions());
                $uploader->setAllowRenameFiles(false);
                $uploader->addValidateCallback('size', $this, 'validateMaxSize');
                $result = $uploader->save($uploadDir);
            } catch (Exception $e) {
                throw new LocalizedException(__('%1', $e->getMessage()));
            }
            if ($result !== false) {
                $filename = $result['file'];
                if ($this->_addWhetherScopeInfo()) {
                    $filename = $this->_prependScopeInfo($filename);
                }
                $this->setValue($filename);
            }
        } else {
            if (is_array($value) && !empty($value['delete'])) {
                $this->setValue('');
            } elseif (is_array($value) && !empty($value['value'])) {
                $this->setValue($value['value']);
            } else {
                $this->unsValue();
            }
        }

        return $this;
    }
}
