import { bootGame } from "../duel/DuelClient.js";
import { MemoryWizard } from "./memorywizard/MemoryWizard.js";

const options = window.__challengeOptions || {};
const clefUrls = window.__clefUrls || null;

bootGame((duelOptions) => new MemoryWizard({
  ...(duelOptions || options),
  clefUrls,
}));
