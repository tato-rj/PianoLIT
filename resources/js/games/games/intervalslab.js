import { bootGame } from "../duel/DuelClient.js";
import { IntervalsLab } from "./intervalslab/IntervalsLab.js";

const options = window.__challengeOptions || {};
const clefUrls = window.__clefUrls || null;

bootGame((duelOptions) => new IntervalsLab({
  ...(duelOptions || options),
  clefUrls,
}));
