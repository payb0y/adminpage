import axios from "@nextcloud/axios";
import { generateUrl } from "@nextcloud/router";

function pdfUrl(organizationId) {
  return generateUrl(
    `/apps/projectcreatoraio/api/v1/organizations/${organizationId}/default-pdf`,
  );
}

const requestConfig = {
  headers: {
    "OCS-APIRequest": "true",
  },
};

export async function getOrganizationPdfInfo(organizationId) {
  const response = await axios.get(pdfUrl(organizationId), requestConfig);
  return response.data;
}

export async function uploadOrganizationPdf(organizationId, file, fileName) {
  const formData = new FormData();
  formData.append("pdf", file);
  formData.append("fileName", fileName);

  const response = await axios.post(pdfUrl(organizationId), formData, requestConfig);
  return response.data;
}

export async function deleteOrganizationPdf(organizationId) {
  const response = await axios.delete(pdfUrl(organizationId), requestConfig);
  return response.data;
}
