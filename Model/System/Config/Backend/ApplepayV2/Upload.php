<?php

declare(strict_types=1);

namespace Unzer\PAPI\Model\System\Config\Backend\ApplepayV2;

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

    private const DOMAIN_ASSOCIATION_FILE_NAME = 'apple-developer-merchantid-domain-association';

    private const UPLOAD_DIR = '.well-known/';

    /**
     * Retrieve upload directory path
     *
     * @param string $uploadDir
     *
     * @return string
     * @throws FileSystemException
     */
    protected function getUploadDirPath($uploadDir): string
    {
        $this->_mediaDirectory = $this->_filesystem->getDirectoryWrite(DirectoryList::PUB);

        $path = $this->_mediaDirectory->getAbsolutePath($uploadDir);

        if (!$this->_mediaDirectory->isDirectory($path)) {
            $this->_mediaDirectory->create($path);
        }

        return $path;
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
            $uploadDir = $this->getUploadDirPath(self::UPLOAD_DIR);
            try {
                $uploader = $this->_uploaderFactory->create(['fileId' => $file]);
                $uploader->setAllowRenameFiles(false);
                $uploader->addValidateCallback('size', $this, 'validateMaxSize');
                $uploader->setFilesDispersion(false);

                $domainAssocFileName = self::DOMAIN_ASSOCIATION_FILE_NAME;

                $result = $uploader->save($uploadDir, $domainAssocFileName);
            } catch (Exception $e) {
                throw new LocalizedException(__('%1', $e->getMessage()));
            }
            if ($result !== false) {
                if ($this->_addWhetherScopeInfo()) {
                    $domainAssocFileName = $this->_prependScopeInfo($domainAssocFileName);
                }
                $this->setValue($domainAssocFileName);
            }
        } else {
            if (is_array($value) && !empty($value['delete'])) {
                $this->deleteDomainAssocFile();
            } elseif (is_array($value) && !empty($value['value'])) {
                $this->setValue($value['value']);
            } else {
                $this->unsValue();
            }
        }

        return $this;
    }

    /**
     * @return void
     *
     * @throws FileSystemException
     * @throws LocalizedException
     */
    private function deleteDomainAssocFile(): void
    {
        $uploadDir = $this->getUploadDirPath(self::UPLOAD_DIR);
        $filePath = $uploadDir . DIRECTORY_SEPARATOR . self::DOMAIN_ASSOCIATION_FILE_NAME;

        if ($this->_mediaDirectory->isExist($filePath)) {
            try {
                $this->_mediaDirectory->delete($filePath);
            } catch (Exception $e) {
                throw new LocalizedException(__('Unable to delete file: %1', $e->getMessage()));
            }
        }

        $this->setValue('');
    }
}
