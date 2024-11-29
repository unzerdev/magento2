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
    /**
     * Retrieve upload directory path
     *
     * @param string $uploadDir
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
            $uploadDir = $this->getUploadDirPath('.well-known/');
            try {
                $uploader = $this->_uploaderFactory->create(['fileId' => $file]);
                $uploader->setAllowRenameFiles(false);
                $uploader->addValidateCallback('size', $this, 'validateMaxSize');
                $uploader->setFilesDispersion(false);

                $domainAssocFileName = 'apple-developer-merchantid-domain-association';

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
